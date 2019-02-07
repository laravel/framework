<?php

namespace Illuminate\Tests\Integration\Http;

use Throwable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * @group integration
 */
class ThrottleRequestsTest extends TestCase
{
    public function tearDown(): void
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
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 0, 0, 0));

        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/');
        $this->assertEquals('yes', $response->getContent());
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
}
