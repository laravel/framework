<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\Idempotent;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration]
class IdempotentRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected static int $controllerExecutionCount = 0;

    protected function setUp(): void
    {
        parent::setUp();

        static::$controllerExecutionCount = 0;
    }

    public function testFirstRequestCachesResponse()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertJson(['id' => 1]);
        $response->assertHeaderMissing('Idempotency-Replayed');
    }

    public function testSameKeyAndPayloadReplaysResponseWithHeader()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertJson(['id' => 1]);
        $response->assertHeader('Idempotency-Replayed', 'true');
    }

    public function testReplayedResponsePreservesOriginalStatusBodyAndHeaders()
    {
        Route::post('/orders', function () {
            return response()->json(['created' => true], 201)
                ->header('X-Custom', 'value');
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertCreated();
        $response->assertJson(['created' => true]);
        $response->assertHeader('X-Custom', 'value');
        $response->assertHeader('Idempotency-Replayed', 'true');
    }

    public function testReplayedResponseDoesNotExecuteControllerAgain()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testSameKeyWithDifferentPayloadReturns422()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'different'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertUnprocessable();
    }

    public function testSameKeyWithDifferentQueryStringReturns422()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders?source=web', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders?source=mobile', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertUnprocessable();
    }

    public function testSameKeyOnDifferentRouteDoesNotCollide()
    {
        Route::post('/orders', function () {
            return response()->json(['type' => 'order']);
        })->middleware(Idempotent::class)->name('orders.store');

        Route::post('/refunds', function () {
            return response()->json(['type' => 'refund']);
        })->middleware(Idempotent::class)->name('refunds.store');

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/refunds', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertJson(['type' => 'refund']);
        $response->assertHeaderMissing('Idempotency-Replayed');
    }

    public function testSameKeyOnDifferentMethodDoesNotCollide()
    {
        Route::post('/orders', function () {
            return response()->json(['method' => 'post']);
        })->middleware(Idempotent::class);

        Route::put('/orders', function () {
            return response()->json(['method' => 'put']);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->putJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertJson(['method' => 'put']);
        $response->assertHeaderMissing('Idempotency-Replayed');
    }

    public function testConcurrentInFlightDuplicateReturns409WithRetryAfter()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $cache = $this->app->make(Cache::class);

        $storageKey = hash('xxh128', implode('|', [
            '/orders',
            'POST',
            'ip:127.0.0.1',
            'Idempotency-Key',
            'key-conflict',
        ]));

        $cache->lock('idempotent-lock:'.$storageKey, 10)->get();

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-conflict',
        ]);

        $response->assertConflict();
        $response->assertHeader('Retry-After', '1');
    }

    public function testMissingKeyReturns400WhenRequired()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $response = $this->postJson('/orders', ['item' => 'widget']);

        $response->assertBadRequest();
    }

    public function testMissingKeyPassesThroughWhenOptional()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(required: false));

        $response = $this->postJson('/orders', ['item' => 'widget']);

        $response->assertOk();
        $response->assertJson(['id' => 1]);
    }

    public function testCustomHeaderNameWorksWhenConfiguredOnMiddleware()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(header: 'X-Idempotency-Key'));

        $this->postJson('/orders', ['item' => 'widget'], [
            'X-Idempotency-Key' => 'custom-key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'X-Idempotency-Key' => 'custom-key-1',
        ]);

        $response->assertOk();
        $response->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testDefaultHeaderIsIgnoredWhenCustomHeaderIsConfigured()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(header: 'X-Idempotency-Key'));

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertBadRequest();
    }

    public function testUserScopeIsolatesDifferentAuthenticatedUsers()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(scope: 'user'));

        $user1 = User::forceCreate([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::forceCreate([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user1)->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->actingAs($user2)->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertHeaderMissing('Idempotency-Replayed');
    }

    public function testUserScopeFallsBackToIpForGuests()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(scope: 'user'));

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testIpScopeIsolatesByIp()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(scope: 'ip'));

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertHeader('Idempotency-Replayed', 'true');
    }

    public function testGlobalScopeIgnoresUserAndIpSegmentation()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::using(scope: 'global'));

        $user1 = User::forceCreate([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::forceCreate([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($user1)->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->actingAs($user2)->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testNonTargetMethodsPassThroughUntouched()
    {
        Route::get('/orders', function () {
            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        Route::delete('/orders/{id}', function ($id) {
            return response()->json(['deleted' => true]);
        })->middleware(Idempotent::class);

        $this->getJson('/orders')->assertOk();
        $this->deleteJson('/orders/1')->assertOk();
    }

    public function testDeleteRequestsAreNotIdempotencyManaged()
    {
        Route::delete('/orders/{id}', function ($id) {
            return response()->json(['deleted' => true]);
        })->middleware(Idempotent::class);

        $this->deleteJson('/orders/1')->assertOk();
    }

    public function testJsonNormalizationAvoidsFalseMismatchesCausedByKeyOrder()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['b' => 2, 'a' => 1], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->postJson('/orders', ['a' => 1, 'b' => 2], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertOk();
        $response->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testRedirectsCanBeReplayed()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return redirect('/orders/1');
        })->middleware(Idempotent::class);

        $this->post('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response = $this->post('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $response->assertRedirect('/orders/1');
        $response->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testValidationExceptionsDoNotPoisonStoredResponse()
    {
        Route::post('/orders', function (Request $request) {
            $request->validate(['item' => 'required']);

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $this->postJson('/orders', [], [
            'Idempotency-Key' => 'key-1',
        ])->assertUnprocessable();

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-2',
        ]);

        $response->assertOk();
        $response->assertJson(['id' => 1]);
    }

    public function testLockIsReleasedAfterDownstreamExceptions()
    {
        Route::post('/orders', function () {
            throw new \RuntimeException('Boom');
        })->middleware(Idempotent::class);

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ])->assertServerError();

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-1',
        ]);

        $this->assertNotEquals(409, $response->getStatusCode());
    }

    public function testStaticUsingGeneratesCorrectMiddlewareString()
    {
        $this->assertSame(
            Idempotent::class.':86400,1,user,Idempotency-Key',
            Idempotent::using()
        );

        $this->assertSame(
            Idempotent::class.':3600,1,user,Idempotency-Key',
            Idempotent::using(ttl: 3600)
        );

        $this->assertSame(
            Idempotent::class.':86400,0,ip,X-Idempotency-Key',
            Idempotent::using(required: false, scope: 'ip', header: 'X-Idempotency-Key')
        );
    }

    public function testConflictPathDoesNotCacheAPlaceholderResponse()
    {
        Route::post('/orders', function () {
            static::$controllerExecutionCount++;

            return response()->json(['id' => 1]);
        })->middleware(Idempotent::class);

        $cache = $this->app->make(Cache::class);

        $storageKey = hash('xxh128', implode('|', [
            '/orders',
            'POST',
            'ip:127.0.0.1',
            'Idempotency-Key',
            'key-conflict',
        ]));
        $lock = $cache->lock('idempotent-lock:'.$storageKey, 10);
        $lock->get();

        $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-conflict',
        ])->assertConflict();

        $lock->release();

        $response = $this->postJson('/orders', ['item' => 'widget'], [
            'Idempotency-Key' => 'key-conflict',
        ]);

        $response->assertOk();
        $response->assertJson(['id' => 1]);
        $this->assertSame(1, static::$controllerExecutionCount);
    }

    public function testPatchRequestsAreIdempotencyManaged()
    {
        Route::patch('/orders/{id}', function ($id) {
            return response()->json(['updated' => true]);
        })->middleware(Idempotent::class);

        $this->patchJson('/orders/1', ['item' => 'widget'])->assertBadRequest();
    }

    public function testPutRequestsAreIdempotencyManaged()
    {
        Route::put('/orders/{id}', function ($id) {
            return response()->json(['updated' => true]);
        })->middleware(Idempotent::class);

        $this->putJson('/orders/1', ['item' => 'widget'])->assertBadRequest();
    }
}
