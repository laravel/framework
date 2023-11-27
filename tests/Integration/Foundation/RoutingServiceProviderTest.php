<?php

namespace Illuminate\Tests\Integration\Foundation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class RoutingServiceProviderTest extends TestCase
{
    public function testItIncludesMergedDataInServerRequestInterfaceInstancesUsingGetRequests()
    {
        Route::get('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        })->middleware(MergeDataMiddleware::class);

        $response = $this->withoutExceptionHandling()->get('test-route?'.http_build_query([
            'sent' => 'sent-data',
            'overridden' => 'overriden-sent-data',
        ]));

        $response->assertOk();
        $response->assertExactJson([
            'request-data' => 'request-data',
        ]);
    }

    public function testItWorksNormallyWithoutMergeDataMiddlewareWithEmptyRequests()
    {
        Route::get('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        });

        $response = $this->withoutExceptionHandling()->get('test-route', [
            'content-type' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function testItIncludesMergedDataInServerRequestInterfaceInstancesUsingGetJsonRequestsWithContentTypeHeader()
    {
        Route::get('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        })->middleware(MergeDataMiddleware::class);

        $response = $this->getJson('test-route?'.http_build_query([
            'sent' => 'sent-data',
            'overridden' => 'overriden-sent-data',
        ]), [
            'content-type' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'json-data' => 'json-data',
            'merged' => 'replaced-merged-data',
            'overridden' => 'overriden-merged-data',
            'request-data' => 'request-data',
        ]);
    }

    public function testItIncludesMergedDataInServerRequestInterfaceInstancesUsingGetJsonRequests()
    {
        Route::get('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        })->middleware(MergeDataMiddleware::class);

        $response = $this->getJson('test-route?'.http_build_query([
            'sent' => 'sent-data',
            'overridden' => 'overriden-sent-data',
        ]));

        $response->assertOk();
        $response->assertExactJson([
            'json-data' => 'json-data',
            'merged' => 'replaced-merged-data',
            'overridden' => 'overriden-merged-data',
            'request-data' => 'request-data',
        ]);
    }

    public function testItIncludesMergedDataInServerRequestInterfaceInstancesUsingPostRequests()
    {
        Route::post('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        })->middleware(MergeDataMiddleware::class);

        $response = $this->post('test-route', [
            'sent' => 'sent-data',
            'overridden' => 'overriden-sent-data',
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'sent' => 'sent-data',
            'merged' => 'replaced-merged-data',
            'overridden' => 'overriden-merged-data',
            'request-data' => 'request-data',
        ]);
    }

    public function testItIncludesMergedDataInServerRequestInterfaceInstancesUsingPostJsonRequests()
    {
        Route::post('test-route', function (ServerRequestInterface $request) {
            return $request->getParsedBody();
        })->middleware(MergeDataMiddleware::class);

        $response = $this->postJson('test-route', [
            'sent' => 'sent-data',
            'overridden' => 'overriden-sent-data',
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'json-data' => 'json-data',
            'sent' => 'sent-data',
            'merged' => 'replaced-merged-data',
            'overridden' => 'overriden-merged-data',
            'request-data' => 'request-data',
        ]);
    }

    public function testItHandlesGzippedBodyPayloadsWhenCreatingServerRequestInterfaceInstances()
    {
        Route::post('test-route', function (ServerRequestInterface $request) {
            return gzdecode((string) $request->getBody());
        });

        $response = $this->call('POST', 'test-route', content: file_get_contents(__DIR__.'/Fixtures/laravel.txt.gz'));

        $response->assertOk();
        $response->assertContent("Laravel\n");
    }
}

class MergeDataMiddleware
{
    public function handle(Request $request, $next)
    {
        $request->merge(['merged' => 'first-merged-data']);

        $request->merge(['merged' => 'replaced-merged-data']);

        $request->merge(['overridden' => 'overriden-merged-data']);

        $request->request->set('request-data', 'request-data');

        $request->query->set('query-data', 'query-data');

        $request->json()->set('json-data', 'json-data');

        return $next($request);
    }
}
