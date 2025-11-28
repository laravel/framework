<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
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

    public function testPoolCanReceiveCallback(): void
    {
        Http::fake([
            'https://laravel.com*' => Http::response('Laravel'),
            'https://forge.laravel.com*' => Http::response('Forge'),
            'https://nightwatch.laravel.com*' => Http::response('Tim n Jess'),
        ]);

        $responses = Http::pool(function (Pool $pool) {
            $pool->as('laravel', function (PendingRequest $request) {
                $request->get('https://laravel.com');
            });
            $pool->as(
                'forge',
                fn (PendingRequest $request) => $request->get('https://forge.laravel.com')
                    ->then(fn (Response $response): int => strlen($response->getBody()))
            );
            $pool->as('nightwatch')->get('https://nightwatch.laravel.com');
        }, 3);

        $this->assertInstanceOf(Response::class, $responses['laravel']);
        $this->assertEquals(5, $responses['forge']);
        $this->assertEquals('Tim n Jess', $responses['nightwatch']->getBody());

        $this->assertCount(3, Http::recorded());
    }
}
