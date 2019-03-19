<?php

namespace Illuminate\Tests\Integration\Http;

use Throwable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;

/**
 * @group integration
 */
class ThrottleRequestsWithRedisTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow(null);
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('hashing', ['driver' => 'bcrypt']);
    }

    public function test_lock_opens_immediately_after_decay()
    {
        $this->ifRedisAvailable(function () {
            $now = Carbon::now();

            Carbon::setTestNow($now);

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
}
