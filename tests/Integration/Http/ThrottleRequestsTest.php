<?php

namespace Illuminate\Tests\Integration\Http;

use Throwable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Tests\Integration\Http\Fixtures\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * @group integration
 */
class ThrottleRequestsTest extends TestCase
{
    public function tearDown()
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

    /**
     * @see test_locks_handles_user_limit_attribute_and_function
     * @return array
     */
    public function provider_test_locks_handles_user_limit_attribute_and_function()
    {
        return [
            ['quota', 10],
            ['quotaLimitFunction', 20],
        ];
    }

    /**
     * @dataProvider provider_test_locks_handles_user_limit_attribute_and_function
     * @param string $maxAttempts
     * @param int $limit
     */
    public function test_locks_handles_user_limit_attribute_and_function($maxAttempts, $limit)
    {
        Route::get('/', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':'.$maxAttempts.',1');

        $response = $this->withoutExceptionHandling()->be(new User)->get('/');
        $this->assertEquals('yes', $response->getContent());
        $this->assertEquals($limit, $response->headers->get('X-RateLimit-Limit'));
    }
}
