<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class HttpClientTest extends TestCase
{
    public function testGlobalMiddlewarePersistsAfterFacadeFlush(): void
    {
        Http::macro('getGlobalMiddleware', fn () => $this->globalMiddleware);
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));

        $this->assertCount(2, Http::getGlobalMiddleware());

        Facade::clearResolvedInstances();

        $this->assertCount(2, Http::getGlobalMiddleware());
    }

    public function testRequestDataDoesNotPersistAfterFlush(): void
    {
        Http::fake([
                       'https://laravel.com' => Http::response('OK', 200)
                   ]);

        $body = Http::get('https://laravel.com')->body();

        $this->assertSame('OK', $body);

        Http::flushFakes();

        Http::fake([
                       'https://laravel.com' => Http::response('Internal Server Error', 500)
                   ]);

        $body = Http::get('https://laravel.com')->body();

        $this->assertSame('Internal Server Error', $body);
    }
}
