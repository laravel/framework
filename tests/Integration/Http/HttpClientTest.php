<?php

namespace Illuminate\Tests\Integration\Http;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Request;
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

    public function testPoolCanForwardToUnderlyingPromise()
    {
        Http::fake([
            'https://laravel.com*' => Http::response('Laravel'),
            'https://forge.laravel.com*' => Http::response('Forge'),
            'https://nightwatch.laravel.com*' => Http::response('Tim n Jess'),
        ]);

        $responses = Http::pool(function (Pool $pool) {
            $pool->as('laravel')->get('https://laravel.com');

            $pool->as('forge')
                ->get('https://forge.laravel.com')
                ->then(function (Response $response): int {
                    return strlen($response->getBody());
                });

            $pool->as('nightwatch')
                ->get('https://nightwatch.laravel.com')
                ->then(fn (): int => 1)
                ->then(fn ($i): int => $i + 199);
        }, 3);

        $this->assertInstanceOf(Response::class, $responses['laravel']);
        $this->assertEquals(5, $responses['forge']);
        $this->assertEquals(200, $responses['nightwatch']);

        $this->assertCount(3, Http::recorded());
    }

    public function testForwardsCallsToPromise()
    {
        Http::fake(['*' => Http::response('faked response')]);

        $myFakedResponse = null;
        $r = Http::async()
            ->get('https://laravel.com')
            ->then(function (Response $response) use (&$myFakedResponse): string {
                $myFakedResponse = $response->getBody();

                return 'stub';
            })
            ->wait();

        $this->assertEquals('faked response', $myFakedResponse);
        $this->assertEquals('stub', $r);
    }

    public function testCanSetRequestAttributes()
    {
        Http::fake([
            '*' => fn (Request $request) => match ($request->attributes()['name'] ?? null) {
                'first' => Http::response('first response'),
                'second' => Http::response('second response'),
                default => Http::response('unnamed')
            },
        ]);

        $response1 = Http::withAttributes(['name' => 'first'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response2 = Http::withAttributes(['name' => 'second'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response3 = Http::get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');
        $response4 = Http::withAttributes(['name' => 'fourth'])->get('https://some-store.myshopify.com/admin/api/2025-10/graphql.json');

        $this->assertEquals('first response', $response1->body());
        $this->assertEquals('second response', $response2->body());
        $this->assertEquals('unnamed', $response3->body());
        $this->assertEquals('unnamed', $response4->body());
    }
}
