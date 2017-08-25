<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;

/**
 * @group integration
 */
class ThrottleRequestsWithRedisTest extends TestCase
{
    public function test_lock_opens_immediately_after_decay()
    {
        Carbon::setTestNow(null);

        resolve('redis')->flushall();

        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequestsWithRedis::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(0, $response->headers->get('X-RateLimit-Remaining'));

        Carbon::setTestNow(
            Carbon::now()->addSeconds(58)
        );

        try {
            $response = $this->withoutExceptionHandling()->get('/');
        } catch (\Throwable $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertTrue(in_array($e->getHeaders()['Retry-After'], [2, 3]));
            $this->assertTrue(in_array($e->getHeaders()['X-RateLimit-Reset'], [now()->timestamp + 2, now()->timestamp + 3]));
        }
    }
}
