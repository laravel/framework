<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\GlobalLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\MissingRateLimiterException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;

#[WithConfig('hashing.driver', 'bcrypt')]
#[WithMigration]
class ThrottleRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function testLockOpensImmediatelyAfterDecay()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 58));

        try {
            $this->withoutExceptionHandling()->get('/');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ThrottleRequestsException::class, $e);
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(2, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(2)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testLimitingUsingNamedLimiter()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);

        $rateLimiter->for('test', function ($request) {
            return new GlobalLimit(2, 1);
        });

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':test');

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 58));

        try {
            $this->withoutExceptionHandling()->get('/');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ThrottleRequestsException::class, $e);
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(2, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(2)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) ThrottleRequests::using('gold-tier');
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:gold-tier', $signature);

        $signature = (string) ThrottleRequests::with(25);
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:25', $signature);

        $signature = (string) ThrottleRequests::with(25, 2);
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:25,2', $signature);

        $signature = (string) ThrottleRequests::with(25, 2, 'foo');
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:25,2,foo', $signature);

        $signature = (string) ThrottleRequests::with(maxAttempts: 25, decayMinutes: 2, prefix: 'foo');
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:25,2,foo', $signature);

        $signature = (string) ThrottleRequests::with(prefix: 'foo');
        $this->assertSame('Illuminate\Routing\Middleware\ThrottleRequests:60,1,foo', $signature);
    }

    public static function perMinuteThrottlingDataSet()
    {
        return [
            [ThrottleRequests::using('test')],
            [ThrottleRequests::with(maxAttempts: 3, decayMinutes: 1)],
            ['throttle:3,1'],
        ];
    }

    #[DataProvider('perMinuteThrottlingDataSet')]
    public function testItCanThrottlePerMinute(string $middleware)
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(3));
        Route::get('/', fn () => 'ok')->middleware($middleware);

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Make 3 requests, each a second apart, that should all be successful.

        for ($i = 0; $i < 3; $i++) {
            match ($i) {
                0 => $this->assertSame('2000-01-01 00:00:00.000', now()->toDateTimeString('m')),
                1 => $this->assertSame('2000-01-01 00:00:01.000', now()->toDateTimeString('m')),
                2 => $this->assertSame('2000-01-01 00:00:02.000', now()->toDateTimeString('m')),
            };

            $response = $this->get('/');
            $response->assertOk();
            $response->assertContent('ok');
            $response->assertHeader('X-RateLimit-Limit', 3);
            $response->assertHeader('X-RateLimit-Remaining', 3 - ($i + 1));

            Carbon::setTestNow(now()->addSecond());
        }

        // It is now 3 seconds past and we will make another request that
        // should be rate limited.

        $this->assertSame('2000-01-01 00:00:03.000', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After', 57);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(57)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We will now make it the very end of the minute, to check boundaries,
        // and make another request that should be rate limited and tell us to
        // try again in 1 second.
        Carbon::setTestNow('2000-01-01 00:00:59.999');

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(1)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We now tick over into the next second. We should now be able to make
        // requests again.
        Carbon::setTestNow('2000-01-01 00:01:00.000');

        $response = $this->get('/');
        $response->assertOk();
    }

    public function testItCanThrottlePerSecond()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perSecond(3));
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Make 3 requests, each a 100ms apart, that should all be successful.

        for ($i = 0; $i < 3; $i++) {
            match ($i) {
                0 => $this->assertSame('2000-01-01 00:00:00.000', now()->toDateTimeString('m')),
                1 => $this->assertSame('2000-01-01 00:00:00.100', now()->toDateTimeString('m')),
                2 => $this->assertSame('2000-01-01 00:00:00.200', now()->toDateTimeString('m')),
            };

            $response = $this->get('/');
            $response->assertOk();
            $response->assertContent('ok');
            $response->assertHeader('X-RateLimit-Limit', 3);
            $response->assertHeader('X-RateLimit-Remaining', 3 - ($i + 1));

            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        // It is now 300 milliseconds past and we will make another request
        // that should be rate limited.

        $this->assertSame('2000-01-01 00:00:00.300', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSecond()->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We will now make it the very end of the minute, to check boundaries,
        // and make another request that should be rate limited and tell us to
        // try again in 1 second.
        Carbon::setTestNow('2000-01-01 00:00:00.999');

        $response = $this->get('/');
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSecond()->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We now tick over into the next second. We should now be able to make
        // requests again.
        Carbon::setTestNow('2000-01-01 00:00:01.000');

        $response = $this->get('/');
        $response->assertOk();
    }

    public function testItCanCombineRateLimitsWithoutSpecifyingUniqueKeys()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => [
            Limit::perSecond(3),
            Limit::perMinute(5),
        ]);
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Make 3 requests, each a 100ms apart, that should all be successful.

        for ($i = 0; $i < 3; $i++) {
            match ($i) {
                0 => $this->assertSame('2000-01-01 00:00:00.000', now()->toDateTimeString('m')),
                1 => $this->assertSame('2000-01-01 00:00:00.100', now()->toDateTimeString('m')),
                2 => $this->assertSame('2000-01-01 00:00:00.200', now()->toDateTimeString('m')),
            };

            $response = $this->get('/');
            $response->assertOk();
            $response->assertContent('ok');

            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        // It is now 300 milliseconds past and we will make another request
        // that should be rate limited.

        $this->assertSame('2000-01-01 00:00:00.300', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSecond()->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We will now make it the very end of the second, to check boundaries,
        // and make another request that should be rate limited and tell us to
        // try again in 1 second.
        Carbon::setTestNow('2000-01-01 00:00:00.999');

        $response = $this->get('/');
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSecond()->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We now tick over into the next second. We should now be able to make
        // another two requests before the per minute rate limit kicks in.
        Carbon::setTestNow('2000-01-01 00:00:01.000');

        for ($i = 0; $i < 2; $i++) {
            match ($i) {
                0 => $this->assertSame('2000-01-01 00:00:01.000', now()->toDateTimeString('m')),
                1 => $this->assertSame('2000-01-01 00:00:01.100', now()->toDateTimeString('m')),
            };

            $response = $this->get('/');
            $response->assertOk();
            $response->assertContent('ok');

            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        // The per minute rate limiter should now fail.

        $this->assertSame('2000-01-01 00:00:01.200', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After', 59);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(59)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 5);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    public function testItFailsIfNamedLimiterDoesNotExist()
    {
        $this->expectException(MissingRateLimiterException::class);
        $this->expectExceptionMessage('Rate limiter [test] is not defined.');

        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        $this->withoutExceptionHandling()->get('/');
    }

    public function testItFailsIfNamedLimiterDoesNotExistAndAuthenticatedUserDoesNotHaveFallbackProperty()
    {
        $this->expectException(MissingRateLimiterException::class);
        $this->expectExceptionMessage('Rate limiter ['.User::class.'::rateLimiting] is not defined.');

        Route::get('/', fn () => 'ok')->middleware(['auth', ThrottleRequests::using('rateLimiting')]);

        // The reason we're enabling strict mode and actually creating a user is to ensure we never even try to access
        // a property within the user model that does not exist. If an application is in strict mode and there is
        // no matching rate limiter, it should throw a rate limiter exception, not a property access exception.
        Model::shouldBeStrict();
        $user = User::forceCreate([
            'name' => 'Mateus',
            'email' => 'mateus@example.org',
            'password' => 'password',
        ]);

        $this->withoutExceptionHandling()->actingAs($user)->get('/');
    }

    public function testItFallbacksToUserPropertyWhenThereIsNoNamedLimiterWhenAuthenticated()
    {
        $user = User::make()->forceFill([
            'rateLimiting' => 1,
        ]);

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        // The `rateLimiting` named limiter does not exist, but the `rateLimiting` property on the
        // User model does, so it should fallback to that property within the authenticated model.
        Route::get('/', fn () => 'yes')->middleware(['auth', ThrottleRequests::using('rateLimiting')]);

        $response = $this->withoutExceptionHandling()->actingAs($user)->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 58));

        try {
            $this->withoutExceptionHandling()->actingAs($user)->get('/');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ThrottleRequestsException::class, $e);
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(1, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(2, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(2)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testItFallbacksToUserAccessorWhenThereIsNoNamedLimiterWhenAuthenticated()
    {
        $user = UserWithAccessor::make();

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        // The `rateLimiting` named limiter does not exist, but the `rateLimiting` accessor (not property!)
        // on the User model does, so it should fallback to that accessor within the authenticated model.
        Route::get('/', fn () => 'yes')->middleware(['auth', ThrottleRequests::using('rateLimiting')]);

        $response = $this->withoutExceptionHandling()->actingAs($user)->get('/');
        $this->assertSame('yes', $response->getContent());
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 58));

        try {
            $this->withoutExceptionHandling()->actingAs($user)->get('/');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ThrottleRequestsException::class, $e);
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(1, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(2, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(2)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testItCanThrottleBasedOnResponse()
    {
        RateLimiterFacade::for('throttle-not-found', function (Request $request) {
            return Limit::perMinute(1)->after(fn ($response) => $response->status() === 404);
        });
        Route::get('/', fn () => match (request('status')) {
            '404' => abort(404),
            default => 'ok',
        })->middleware(ThrottleRequests::using('throttle-not-found'));

        $this->travelTo('2000-01-01 00:00:00');
        $this->get('?status=404')->assertNotFound();
        $this->get('?status=404')->assertTooManyRequests();
        $this->get('?status=404')->assertTooManyRequests();

        $this->travelTo('2000-01-01 00:00:59');
        $this->get('?status=404')->assertTooManyRequests();
        $this->get('?status=404')->assertTooManyRequests();

        $this->travelTo('2000-01-01 00:01:00');
        $this->get('?status=404')->assertNotFound();
        $this->get('?status=404')->assertTooManyRequests();
        $this->get('?status=404')->assertTooManyRequests();

        $this->travelTo('2000-01-01 00:01:59');
        $this->get('?status=404')->assertTooManyRequests();
        $this->get('?status=404')->assertTooManyRequests();

        $this->travelTo('2000-01-01 00:02:00');
        $this->get('?status=404')->assertNotFound();
        $this->get('?status=404')->assertTooManyRequests();
        $this->get('?status=404')->assertTooManyRequests();
    }

    public function testItDoesNotHitLimiterUntilResponseHasBeenGenerated()
    {
        ThrottleRequests::shouldHashKeys(false);
        RateLimiterFacade::for('throttle-not-found', function (Request $request) {
            return Limit::perMinute(1)->after(fn ($response) => $response->status() === 404);
        });
        $duringRequest = null;
        Route::get('/', function () use (&$duringRequest) {
            $duringRequest = [
                Cache::get('throttle-not-found:'),
                Cache::get('throttle-not-found::timer'),
            ];

            abort(404);
        })->middleware(ThrottleRequests::using('throttle-not-found'));

        $this->travelTo('2000-01-01 00:00:00');
        $this->get('?status=404')->assertNotFound();

        $this->assertSame([null, null], $duringRequest);
        $this->assertSame([1, 946684860], [
            Cache::get('throttle-not-found:'),
            Cache::get('throttle-not-found::timer'),
        ]);
    }

    public function testItReturnsConfiguredResponseWhenUsingAfterLimit(): void
    {
        ThrottleRequests::shouldHashKeys(false);
        RateLimiterFacade::for('throttle-not-found', function (Request $request) {
            return Limit::perMinute(1)
                ->after(fn ($response) => $response->status() === 404)
                ->response(fn () => response('ah ah ah', status: 429));
        });
        Route::get('/', fn () => abort(404))->middleware(ThrottleRequests::using('throttle-not-found'));

        $this->travelTo('2000-01-01 00:00:00');
        $this->get('?status=404')->assertNotFound();
        $this->get('?status=404')->assertTooManyRequests()->assertContent('ah ah ah');
    }

    public function testItCanThrottlePerMinuteUsingSlidingWindow()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(3)->slidingWindow());
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Make 3 requests that should all succeed.
        for ($i = 0; $i < 3; $i++) {
            $response = $this->get('/');
            $response->assertOk();
            $response->assertContent('ok');
            $response->assertHeader('X-RateLimit-Limit', 3);
            $response->assertHeader('X-RateLimit-Remaining', 3 - ($i + 1));

            Carbon::setTestNow(now()->addSecond());
        }

        // 4th request should be throttled.
        $this->assertSame('2000-01-01 00:00:03.000', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);
        $response->assertHeader('Retry-After');
    }

    public function testSlidingWindowPreventsWindowBoundaryBurst()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(3)->slidingWindow());
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Max out the limiter at the start of the first window.
        for ($i = 0; $i < 3; $i++) {
            $this->get('/')->assertOk();
        }

        // Move just past the window boundary (1 second into new window).
        // With fixed window this would allow 3 more, but sliding window
        // carries over: effective = floor((59/60) * 3) + 0 = 2
        Carbon::setTestNow('2000-01-01 00:01:01.000');

        // First request should succeed (effective = 2, under limit of 3).
        $this->get('/')->assertOk();

        // Second request should be blocked (effective = 2 + 1 = 3).
        $response = $this->get('/');
        $response->assertStatus(429);
    }

    public function testSlidingWindowAllowsRequestsAfterSufficientTimeElapsed()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(3)->slidingWindow());
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Max out the limiter.
        for ($i = 0; $i < 3; $i++) {
            $this->get('/')->assertOk();
        }

        $this->get('/')->assertStatus(429);

        // Move forward 90 seconds (30s into second window).
        // effective = floor((30/60) * 3) + 0 = 1, under limit of 3.
        Carbon::setTestNow('2000-01-01 00:01:30.000');

        $this->get('/')->assertOk();
    }

    public function testSlidingWindow429ResponseIncludesCorrectHeaders()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(1)->slidingWindow());
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        $this->get('/')->assertOk();

        // Move 30 seconds forward and trigger throttle.
        Carbon::setTestNow('2000-01-01 00:00:30.000');

        try {
            $this->withoutExceptionHandling()->get('/');
        } catch (Throwable $e) {
            $this->assertInstanceOf(ThrottleRequestsException::class, $e);
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(1, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(30, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(30)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
        }
    }

    public function testSlidingWindowWithAfterCallback()
    {
        RateLimiterFacade::for('throttle-not-found', function (Request $request) {
            return Limit::perMinute(1)
                ->slidingWindow()
                ->after(fn ($response) => $response->status() === 404);
        });
        Route::get('/', fn () => match (request('status')) {
            '404' => abort(404),
            default => 'ok',
        })->middleware(ThrottleRequests::using('throttle-not-found'));

        $this->travelTo('2000-01-01 00:00:00');

        // Successful response should not count against the limiter.
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();

        // 404 triggers the after callback and counts.
        $this->get('?status=404')->assertNotFound();

        // Now throttled.
        $this->get('?status=404')->assertTooManyRequests();

        // Move 30 seconds — still in sliding window.
        $this->travelTo('2000-01-01 00:00:30');
        $this->get('?status=404')->assertTooManyRequests();

        // Move past two full windows — fully expired.
        $this->travelTo('2000-01-01 00:02:01');
        $this->get('?status=404')->assertNotFound();
    }

    public function testMixedFixedAndSlidingWindowLimits()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => [
            Limit::perSecond(3)->by('user-1'),
            Limit::perMinute(5)->by('user-1')->slidingWindow(),
        ]);
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');

        // Make 3 requests — should hit per-second fixed limit.
        for ($i = 0; $i < 3; $i++) {
            $this->get('/')->assertOk();
            Carbon::setTestNow(now()->addMilliseconds(100));
        }

        // Per-second limit hit.
        $this->get('/')->assertStatus(429);

        // Move to next second — per-second limit resets (fixed window).
        Carbon::setTestNow('2000-01-01 00:00:01.000');

        // Make 2 more requests — now 5 total against the per-minute sliding window.
        $this->get('/')->assertOk();
        $this->get('/')->assertOk();

        // Per-minute sliding window limit hit (5 total).
        $this->get('/')->assertStatus(429);

        // Move to next second — per-second resets but per-minute sliding is still active.
        Carbon::setTestNow('2000-01-01 00:00:02.000');
        $this->get('/')->assertStatus(429);
    }
}

class UserWithAccessor extends User
{
    public function getRateLimitingAttribute(): int
    {
        return 1;
    }
}
