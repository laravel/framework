<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Container\Container;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class VitePreloadingTest extends TestCase
{
    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
    }

    public function testItDoesNotSetLinkTagWhenNoTagsHaveBeenPreloaded()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertNull($response->headers->get('Link'));
    }

    public function testItAddsPreloadLinkHeader()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://laravel.com/app.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertSame(
            '<https://laravel.com/app.js>; rel="modulepreload"; foo="bar"',
            $response->headers->get('Link'),
        );
    }

    public function testItDoesNotAttachHeadersToNonIlluminateResponses()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://laravel.com/app.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new SymfonyResponse('Hello Laravel');
        });

        $this->assertNull($response->headers->get('Link'));
    }

    public function testItDoesNotOverwriteOtherLinkHeaders()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://laravel.com/app.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel', headers: ['Link' => '<https://laravel.com/logo.png>; rel="preload"; as="image"']);
        });

        $this->assertSame(
            [
                '<https://laravel.com/logo.png>; rel="preload"; as="image"',
                '<https://laravel.com/app.js>; rel="modulepreload"; foo="bar"',
            ],
            $response->headers->all('Link'),
        );
    }
}
