<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\GlobalLimit;
use Illuminate\Container\Container;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithConfig('hashing.driver', 'bcrypt')]
class ThrottleRequestsTest extends TestCase
{
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
}
