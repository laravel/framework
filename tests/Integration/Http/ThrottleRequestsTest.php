<?php

namespace Illuminate\Tests\Integration\Http;

use Throwable;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * @group integration
 */
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

    public function test_routes_use_different_throttle_buckets()
    {
        $cb = function() {
            return 'yes';
        };
        $throttle = ThrottleRequests::class . ':3,1';

        // All these routes are unique and should have a seperate throttle bucket.
        Route::get('/route1', $cb)->middleware($throttle);
        Route::get('/route2', $cb)->middleware($throttle);
        Route::put('/route2', $cb)->middleware($throttle);
        // Route::put('/route2', $cb)->domain('http://some-domain.dev')->middleware($throttle);

        // Call all the routes once to initialise the buckets.
        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if ($method == 'HEAD') {
                    continue;
                }
                $this->withoutExceptionHandling()->call($method, $route->uri());
            }
        }

        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if ($method == 'HEAD') {
                    continue;
                }

                // Call route second time to make sure each route shared it's own bucket.
                $response = $this->withoutExceptionHandling()->call($method, $route->uri());
                
                $this->assertEquals(3, $response->headers->get('X-RateLimit-Limit'));
                $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'), 'For route: ' . json_encode($route));
            }
        }
    }

    public function test_client_ip_use_different_throttle_buckets()
    {
        Route::get('foobar', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/foobar', [
            'REMOTE_ADDR' => '4.4.4.4',
        ]);
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/foobar', [
            'REMOTE_ADDR' => '8.8.8.8',
        ]);
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_auth_id_is_used_when_available()
    {
        Route::get('foobar', function () {
            return 'yes';
        })->middleware(ThrottleRequests::class.':2,1');

        $response = $this->withoutExceptionHandling()->get('/foobar');
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $user = new class extends User {
            public function getAuthIdentifier()
            {
                return '111';
            }
        };
        $this->app['auth']->login($user);

        $response = $this->withoutExceptionHandling()->get('/foobar');
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_grouping_into_seperate_buckets()
    {
        Route::group(['middleware' => ThrottleRequests::class.':2,1,api'], function() {
            Route::get('api-route', function () {
                return 'yes';
            });
        });

        Route::group(['middleware' => ThrottleRequests::class.':5,2,web'], function() {
            Route::get('web-route', function () {
                return 'yes';
            });
        });

        $response = $this->withoutExceptionHandling()->get('/api-route');
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/web-route');
        $this->assertEquals(5, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(4, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_grouping_uses_client_ip()
    {
        Route::group(['middleware' => ThrottleRequests::class.':2,1,api'], function() {
            Route::get('api-route', function () {
                return 'yes';
            });
        });

        $response = $this->withoutExceptionHandling()->get('/api-route', [
            'REMOTE_ADDR' => '4.4.4.4',
        ]);
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $response = $this->withoutExceptionHandling()->get('/api-route', [
            'REMOTE_ADDR' => '8.8.8.8',
        ]);
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_grouping_uses_auth_id_when_available()
    {
        Route::group(['middleware' => ThrottleRequests::class.':2,1,api'], function() {
            Route::get('api-route', function () {
                return 'yes';
            });
        });

        $response = $this->withoutExceptionHandling()->get('/api-route');
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));

        $user = new class extends User {
            public function getAuthIdentifier()
            {
                return '111';
            }
        };
        $this->app['auth']->login($user);

        $response = $this->withoutExceptionHandling()->get('/api-route');
        $this->assertEquals(2, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(1, $response->headers->get('X-RateLimit-Remaining'));
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
