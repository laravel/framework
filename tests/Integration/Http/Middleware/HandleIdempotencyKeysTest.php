<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Middleware\HandleIdempotencyKeys;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('cache.default', 'array')]
class HandleIdempotencyKeysTest extends TestCase
{
    protected $executions = 0;

    public function testResponseIsReplayedForTheSameKey()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])
            ->assertOk()
            ->assertExactJson(['id' => 1, 'item' => 'book'])
            ->assertHeaderMissing('Idempotent-Replayed');

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Idempotent-Replayed', 'true')
            ->assertExactJson(['id' => 1, 'item' => 'book']);

        $this->assertSame(1, $this->executions);
    }

    public function testDifferentKeysAreProcessedIndependently()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-2'])->assertJson(['id' => 2]);

        $this->assertSame(2, $this->executions);
    }

    public function testRequestsWithoutKeyAreProcessedNormally()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'])->assertJson(['id' => 1]);
        $this->postJson('/orders', ['item' => 'book'])->assertJson(['id' => 2]);

        $this->assertSame(2, $this->executions);
    }

    public function testKeyMayBeRequired()
    {
        $this->registerRoute(HandleIdempotencyKeys::using(required: true));

        $this->postJson('/orders', ['item' => 'book'])
            ->assertBadRequest()
            ->assertJson(['message' => 'The Idempotency-Key header is required.']);

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();

        $this->assertSame(1, $this->executions);
    }

    public function testKeyMayBeRequiredUsingMiddlewareParameters()
    {
        $this->registerRoute(HandleIdempotencyKeys::class.':required');

        $this->postJson('/orders', ['item' => 'book'])->assertBadRequest();

        $this->assertSame(0, $this->executions);
    }

    public function testIdempotentMethodsAreIgnored()
    {
        Route::match(['GET', 'PUT', 'DELETE'], '/orders', function () {
            return ['id' => ++$this->executions];
        })->middleware(HandleIdempotencyKeys::using(required: true));

        $this->getJson('/orders', ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->putJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);
        $this->deleteJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 3]);
        $this->getJson('/orders')->assertJson(['id' => 4]);
    }

    public function testPatchRequestsAreHandled()
    {
        Route::patch('/orders', function () {
            return ['id' => ++$this->executions];
        })->middleware(HandleIdempotencyKeys::class);

        $this->patchJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->patchJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->assertSame(1, $this->executions);
    }

    public function testReusingKeyWithDifferentPayloadIsRejected()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();

        $this->postJson('/orders', ['item' => 'pen'], ['Idempotency-Key' => 'key-1'])
            ->assertUnprocessable()
            ->assertJson(['message' => 'The idempotency key has already been used for a different request.']);

        $this->assertSame(1, $this->executions);
    }

    public function testKeysAreScopedToTheRequestedUrl()
    {
        $this->registerRoute();
        $this->registerRoute(uri: '/refunds');

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->postJson('/refunds', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->postJson('/refunds', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);

        $this->assertSame(2, $this->executions);
    }

    public function testKeysAreScopedToTheRequestMethod()
    {
        Route::match(['POST', 'PATCH'], '/orders', function () {
            return ['id' => ++$this->executions];
        })->middleware(HandleIdempotencyKeys::class);

        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->patchJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);
        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->assertSame(2, $this->executions);
    }

    public function testKeysAreNotBlockedByInFlightRequestsToOtherUrls()
    {
        $this->registerRoute(uri: '/refunds');

        $lock = Cache::lock($this->lockKey('key-1', '/orders'), 10);

        $this->assertTrue($lock->get());

        $this->postJson('/refunds', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();

        $lock->release();
    }

    public function testUploadedFilesArePartOfTheFingerprint()
    {
        $this->registerRoute();

        $this->post('/orders', ['receipt' => UploadedFile::fake()->createWithContent('receipt.txt', 'foo')], ['Idempotency-Key' => 'key-1'])->assertOk();
        $this->post('/orders', ['receipt' => UploadedFile::fake()->createWithContent('receipt.txt', 'foo')], ['Idempotency-Key' => 'key-1'])->assertHeader('Idempotent-Replayed', 'true');
        $this->post('/orders', ['receipt' => UploadedFile::fake()->createWithContent('receipt.txt', 'bar')], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();
        $this->post('/orders', ['receipt' => UploadedFile::fake()->createWithContent('invoice.txt', 'foo')], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();
        $this->post('/orders', ['receipt' => UploadedFile::fake()->createWithContent('receipt.exe', 'foo')], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();

        file_put_contents($path = tempnam(sys_get_temp_dir(), 'receipt'), 'foo');

        $this->post('/orders', ['receipt' => new UploadedFile($path, 'receipt.txt', 'application/octet-stream', null, true)], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();

        unlink($path);

        $this->assertSame(1, $this->executions);
    }

    public function testRawBodiesArePartOfTheFingerprint()
    {
        $this->registerRoute();

        $this->call('POST', '/orders', server: $this->serverVariables('application/xml'), content: '<order>book</order>')->assertOk();
        $this->call('POST', '/orders', server: $this->serverVariables('application/xml'), content: '<order>book</order>')->assertHeader('Idempotent-Replayed', 'true');
        $this->call('POST', '/orders', server: $this->serverVariables('application/xml'), content: '<order>pen</order>')->assertUnprocessable();

        $this->assertSame(1, $this->executions);
    }

    public function testJsonKeyOrderDoesNotAffectTheFingerprint()
    {
        $this->registerRoute();

        $this->call('POST', '/orders', server: $this->serverVariables(), content: '{"amount":100,"currency":"GBP","items":[1,2]}')->assertOk();
        $this->call('POST', '/orders', server: $this->serverVariables(), content: '{"currency":"GBP","amount":100,"items":[1,2]}')->assertHeader('Idempotent-Replayed', 'true');
        $this->call('POST', '/orders', server: $this->serverVariables(), content: '{"amount":100,"currency":"GBP","items":[2,1]}')->assertUnprocessable();

        $this->assertSame(1, $this->executions);
    }

    public function testQueryStringIsFingerprintedSeparatelyFromTheBody()
    {
        $this->registerRoute();

        $this->postJson('/orders?item=book', [], ['Idempotency-Key' => 'key-1'])->assertOk();
        $this->postJson('/orders?item=book', [], ['Idempotency-Key' => 'key-1'])->assertHeader('Idempotent-Replayed', 'true');
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();
        $this->postJson('/orders?item=pen', [], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();

        $this->assertSame(1, $this->executions);
    }

    public function testContentTypeIsPartOfTheFingerprint()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();
        $this->post('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();

        $this->assertSame(1, $this->executions);
    }

    public function testConcurrentRequestsWithSameKeyConflict()
    {
        $this->registerRoute();

        $lock = Cache::lock($this->lockKey('key-1'), 10);

        $this->assertTrue($lock->get());

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])
            ->assertConflict()
            ->assertJson(['message' => 'A request with this idempotency key is currently being processed.']);

        $lock->release();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();

        $this->assertSame(1, $this->executions);
    }

    public function testLockIsReleasedAfterProcessing()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk();

        $this->assertTrue(Cache::lock($this->lockKey('key-1'), 10)->get());
    }

    public function testLockDurationMayBeCustomized()
    {
        Route::post('/default', function () {
            $this->travel(2)->seconds();

            return ['acquired' => Cache::lock($this->lockKey('key-1', '/default'), 10)->get()];
        })->middleware(HandleIdempotencyKeys::class);

        Route::post('/custom', function () {
            $this->travel(2)->seconds();

            return ['acquired' => Cache::lock($this->lockKey('key-1', '/custom'), 10)->get()];
        })->middleware(HandleIdempotencyKeys::using(lock: 1));

        $this->postJson('/default', [], ['Idempotency-Key' => 'key-1'])->assertJson(['acquired' => false]);
        $this->postJson('/custom', [], ['Idempotency-Key' => 'key-1'])->assertJson(['acquired' => true]);
    }

    public function testServerErrorsAreNotStored()
    {
        Route::post('/orders', function () {
            abort_if(++$this->executions === 1, 500);

            return ['id' => $this->executions];
        })->middleware(HandleIdempotencyKeys::class);

        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertServerError();
        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertOk()->assertJson(['id' => 2]);
        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertHeader('Idempotent-Replayed', 'true')->assertJson(['id' => 2]);

        $this->assertSame(2, $this->executions);
    }

    public function testValidationErrorsAreNotStored()
    {
        Route::post('/orders', function (Request $request) {
            $request->validate(['item' => 'required']);

            return ['id' => ++$this->executions];
        })->middleware(HandleIdempotencyKeys::class);

        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();
        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertUnprocessable();
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk()->assertJson(['id' => 1]);
    }

    public function testValidationRedirectsAreNotStored()
    {
        Route::post('/orders', function (Request $request) {
            $request->validate(['item' => 'required']);

            return ['id' => ++$this->executions];
        })->middleware(HandleIdempotencyKeys::class);

        $this->from('/checkout')->post('/orders', [], ['Idempotency-Key' => 'key-1'])->assertRedirect('/checkout');
        $this->post('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertOk()->assertJson(['id' => 1]);
    }

    public function testRedirectsAreReplayed()
    {
        Route::post('/orders', function () {
            return redirect('/orders/'.++$this->executions);
        })->middleware(HandleIdempotencyKeys::class);

        $this->post('/orders', [], ['Idempotency-Key' => 'key-1'])->assertRedirect('/orders/1');
        $this->post('/orders', [], ['Idempotency-Key' => 'key-1'])->assertRedirect('/orders/1')->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame(1, $this->executions);
    }

    public function testCookiesAreNotReplayed()
    {
        Route::post('/orders', function () {
            return response()->json(['id' => ++$this->executions])->cookie('order', 'secret');
        })->middleware(HandleIdempotencyKeys::class);

        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertCookie('order', 'secret', encrypted: false);
        $this->postJson('/orders', [], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1])->assertCookieMissing('order');
    }

    public function testKeysAreScopedToTheAuthenticatedUser()
    {
        $this->registerRoute();

        $taylor = new GenericUser(['id' => 1]);
        $abigail = new GenericUser(['id' => 2]);

        $this->actingAs($taylor)->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->actingAs($abigail)->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);
        $this->actingAs($taylor)->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->assertSame(2, $this->executions);
    }

    public function testStoredResponsesExpire()
    {
        $this->registerRoute(HandleIdempotencyKeys::using(ttl: 60));

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->travel(59)->seconds();
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->travel(2)->seconds();
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 2]);
    }

    public function testQuotedKeysAreEquivalentToUnquotedKeys()
    {
        $this->registerRoute();

        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => '"key-1"'])->assertJson(['id' => 1]);
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->assertSame(1, $this->executions);
    }

    public function testInvalidKeysAreRejected()
    {
        $this->registerRoute();

        $this->postJson('/orders', [], ['Idempotency-Key' => ''])->assertBadRequest();
        $this->postJson('/orders', [], ['Idempotency-Key' => '""'])->assertBadRequest();
        $this->postJson('/orders', [], ['Idempotency-Key' => Str::repeat('a', 256)])
            ->assertBadRequest()
            ->assertJson(['message' => 'The Idempotency-Key header must be between 1 and 255 characters.']);

        $this->assertSame(0, $this->executions);
    }

    public function testMiddlewareCanBeUsedViaAlias()
    {
        $this->registerRoute('idempotent:60,required');

        $this->postJson('/orders', ['item' => 'book'])->assertBadRequest();
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);
        $this->postJson('/orders', ['item' => 'book'], ['Idempotency-Key' => 'key-1'])->assertJson(['id' => 1]);

        $this->assertSame(1, $this->executions);
    }

    public function testUsingGeneratesMiddlewareDefinition()
    {
        $this->assertSame(HandleIdempotencyKeys::class.':86400,60', HandleIdempotencyKeys::using());
        $this->assertSame(HandleIdempotencyKeys::class.':3600,60', HandleIdempotencyKeys::using(3600));
        $this->assertSame(HandleIdempotencyKeys::class.':3600,60,required', HandleIdempotencyKeys::using(3600, true));
        $this->assertSame(HandleIdempotencyKeys::class.':3600,120,required', HandleIdempotencyKeys::using(ttl: 3600, required: true, lock: 120));
    }

    protected function lockKey($key, $uri = '/orders', $method = 'POST', $scope = '127.0.0.1')
    {
        return 'idempotency:'.hash('sha256', serialize([$scope, $method, 'http://localhost'.$uri, $key])).':lock';
    }

    protected function serverVariables($contentType = 'application/json', $key = 'key-1')
    {
        return ['CONTENT_TYPE' => $contentType, 'HTTP_IDEMPOTENCY_KEY' => $key];
    }

    protected function registerRoute($middleware = HandleIdempotencyKeys::class, $uri = '/orders')
    {
        Route::post($uri, function (Request $request) {
            return ['id' => ++$this->executions, 'item' => $request->input('item')];
        })->middleware($middleware);
    }
}
