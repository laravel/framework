<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\GlobalLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Container\Container;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Throwable;

class ThrottleRequestsTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('hashing', ['driver' => 'bcrypt']);
    }

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
            $this->assertEquals(3, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(3)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
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
            $this->assertEquals(3, $e->getHeaders()['Retry-After']);
            $this->assertEquals(Carbon::now()->addSeconds(3)->getTimestamp(), $e->getHeaders()['X-RateLimit-Reset']);
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

    public function testItCanThrottlePerMinute()
    {
        $rateLimiter = Container::getInstance()->make(RateLimiter::class);
        $rateLimiter->for('test', fn () => Limit::perMinute(3));
        Route::get('/', fn () => 'ok')->middleware(ThrottleRequests::using('test'));

        Carbon::setTestNow('2000-01-01 00:00:00.000');
        $startedAt = now();

        // Make 3 requests that should all be successful. The first request is
        // at the VERY start of the second. That is important to remember.
        // Assertions before each request to make sure we know the time.

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
        $response->assertHeader('Retry-After', 58);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(58)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We will now make it the very end of the minute, to check boundaries, and
        // make another request that should be rate limited and tell us to try
        // again in 1 second.
        Carbon::setTestNow(now()->endOfMinute());
        $this->assertSame('2000-01-01 00:00:59.999', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertHeader('Retry-After', 2);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(2)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We now tick over into the next second. A minute has now lapsed since the
        // first request was sent but in this second we not able to make requests
        // as seconds is the smallest precision for the rate limiter.
        Carbon::setTestNow('2000-01-01 00:01:00.000');

        // Confirming it has been an entire minute since the first request and we
        // cannot make requests.
        $this->assertTrue($startedAt->addMinute()->eq(now()));

        $response = $this->get('/');
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(1)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // Testing the second boundary. Technically, with this time travel, it has
        // been 999 milliseconds since the first request, but we don't have that
        // kind of precision on the rate limiter.
        Carbon::setTestNow(now()->endOfSecond());
        $this->assertSame('2000-01-01 00:01:00.999', now()->toDateTimeString('m'));

        $response = $this->get('/');
        $response->assertHeader('Retry-After', 1);
        $response->assertHeader('X-RateLimit-Reset', now()->addSeconds(1)->timestamp);
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 0);

        // We will now tick over one more second. The first request was sent at
        // 00:00:00 and the next request isn't able to be sent until 00:01:01.
        Carbon::setTestNow(now()->addMillisecond());
        Carbon::setTestNow('2000-01-01 00:01:02.000');

        $response = $this->get('/');
        $response->assertOk();
        $response->assertContent('ok');
        $response->assertHeader('X-RateLimit-Limit', 3);
        $response->assertHeader('X-RateLimit-Remaining', 2);
    }
}
