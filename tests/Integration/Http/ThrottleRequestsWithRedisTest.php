<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithConfig('hashing.driver', 'bcrypt')]
class ThrottleRequestsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    public function testLockOpensImmediatelyAfterDecay()
    {
        $this->ifRedisAvailable(function () {
            $now = Carbon::now();

            Carbon::setTestNow($now);

            Route::get('/', function () {
                return 'yes';
            })->middleware(ThrottleRequestsWithRedis::class.':2,1');

            $response = $this->withoutExceptionHandling()->get('/');
            $this->assertSame('yes', $response->getContent());
            $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
            $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

            $response = $this->withoutExceptionHandling()->get('/');
            $this->assertSame('yes', $response->getContent());
            $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
            $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

            Carbon::setTestNow($finish = $now->addSeconds(58));

            try {
                $this->withoutExceptionHandling()->get('/');
            } catch (Throwable $e) {
                $this->assertEquals(429, $e->getStatusCode());
                $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
                $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
                // $this->assertTrue(in_array($e->getHeaders()['Retry-After'], [2, 3]));
                // $this->assertTrue(in_array($e->getHeaders()['X-RateLimit-Reset'], [$finish->getTimestamp() + 2, $finish->getTimestamp() + 3]));
            }
        });
    }

    public function testItCanThrottleBasedOnResponse()
    {
        $this->ifRedisAvailable(function () {
            RateLimiter::for('throttle-not-found', function (Request $request) {
                return Limit::perMinute(1)->after(fn ($response) => $response->status() === 404);
            });
            Route::get('/', fn () => match (request('status')) {
                '404' => abort(404),
                default => 'ok',
            })->middleware(ThrottleRequestsWithRedis::using('throttle-not-found'));

            // Non-matching responses should not count toward the limit.
            $this->get('?status=200')->assertOk();
            $this->get('?status=200')->assertOk();
            $this->get('?status=200')->assertOk();

            // A matching response should count and exhaust the limit.
            $this->get('?status=404')->assertNotFound();

            // Now throttled — even non-matching requests are blocked.
            $this->get('?status=200')->assertTooManyRequests();
            $this->get('?status=404')->assertTooManyRequests();
        });
    }

    public function testItReturnsConfiguredResponseWhenUsingAfterLimit(): void
    {
        $this->ifRedisAvailable(function () {
            RateLimiter::for('throttle-not-found', function (Request $request) {
                return Limit::perMinute(1)
                    ->after(fn ($response) => $response->status() === 404)
                    ->response(fn () => response('ah ah ah', status: 429));
            });
            Route::get('/', fn () => abort(404))->middleware(ThrottleRequestsWithRedis::using('throttle-not-found'));

            $this->get('/')->assertNotFound();
            $this->get('/')->assertTooManyRequests()->assertContent('ah ah ah');
        });
    }
}
