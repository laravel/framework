<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Routing\Middleware\ThrottleRequests;
use JsonSerializable;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @group integration
 */
class ThrottleRequestsTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'redis');
    }

    public function setup()
    {
        parent::setup();

        resolve('redis')->flushall();
    }

    public function test_resources_may_be_converted_to_json()
    {
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

        try{
            $response = $this->withoutExceptionHandling()->get('/');
        }catch(\Throwable $e){
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertEquals(2, $e->getHeaders()['X-RateLimit-Limit']);
            $this->assertEquals(0, $e->getHeaders()['X-RateLimit-Remaining']);
            $this->assertEquals(60, $e->getHeaders()['Retry-After']);
        }
    }
}
