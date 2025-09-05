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

    public function testConnectionWithMissingConfiguration()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection [nonexistent] is not defined.');

        Http::connection('nonexistent');
    }

    public function testConnectionWithValidConfiguration()
    {
        $this->app['config']->set('services.github', [
            'base_url' => 'https://api.github.com',
            'token' => 'test-token',
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);

        Http::fake();

        $response = Http::connection('github')->get('/user');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.github.com/user' &&
                   $request->hasHeader('Authorization', 'Bearer test-token') &&
                   $request->hasHeader('Accept', 'application/vnd.github.v3+json');
        });
    }

    public function testConnectionWithBasicAuth()
    {
        $this->app['config']->set('services.stripe', [
            'base_url' => 'https://api.stripe.com',
            'basic_auth' => [
                'username' => 'user',
                'password' => 'pass',
            ],
        ]);

        Http::fake();
        Http::connection('stripe')->post('/data');

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.stripe.com/data');
        });
    }
}
