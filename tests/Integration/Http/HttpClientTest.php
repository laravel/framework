<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class HttpClientTest extends TestCase
{
    public function testGlobalMiddlewarePersistsBeforeWeDispatchEvent(): void
    {
        Event::fake();
        Http::fake();

        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Facade/1.0'));

        Http::get('laravel.com');

        Event::assertDispatched(RequestSending::class, function (RequestSending $event) {
            return (new Collection($event->request->header('User-Agent')))->contains('Facade/1.0');
        });
    }

    public function testGlobalMiddlewarePersistsAfterFacadeFlush(): void
    {
        Http::macro('getGlobalMiddleware', fn () => $this->globalMiddleware);
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));
        Http::globalRequestMiddleware(fn ($request) => $request->withHeader('User-Agent', 'Example Application/1.0'));

        $this->assertCount(2, Http::getGlobalMiddleware());

        Facade::clearResolvedInstances();

        $this->assertCount(2, Http::getGlobalMiddleware());
    }
}
