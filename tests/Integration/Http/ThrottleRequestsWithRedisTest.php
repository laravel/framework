<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Throwable;

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
}
