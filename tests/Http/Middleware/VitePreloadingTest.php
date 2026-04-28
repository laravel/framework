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

        parent::tearDown();
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

    public function testItCanLimitNumberOfAssetsPreloaded()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://laravel.com/first.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
                'https://laravel.com/second.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
                'https://laravel.com/third.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
                'https://laravel.com/fourth.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, fn () => new Response('ok'), 2);

        $this->assertSame(
            [
                '<https://laravel.com/first.js>; rel="modulepreload"; foo="bar", <https://laravel.com/second.js>; rel="modulepreload"; foo="bar"',
            ],
            $response->headers->all('Link'),
        );
    }

    public function testFontPreloadEntriesResultInLinkHeaders()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://example.com/build/assets/inter-400.woff2' => [
                    'rel="preload"',
                    'as="font"',
                    'type="font/woff2"',
                    'crossorigin="anonymous"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertSame(
            '<https://example.com/build/assets/inter-400.woff2>; rel="preload"; as="font"; type="font/woff2"; crossorigin="anonymous"',
            $response->headers->get('Link'),
        );
    }

    public function testFontPreloadsDoNotOverwriteExistingJsPreloads()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://example.com/build/assets/app.js' => [
                    'rel="modulepreload"',
                ],
                'https://example.com/build/assets/inter-400.woff2' => [
                    'rel="preload"',
                    'as="font"',
                    'type="font/woff2"',
                    'crossorigin="anonymous"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertSame(
            [
                '<https://example.com/build/assets/app.js>; rel="modulepreload", <https://example.com/build/assets/inter-400.woff2>; rel="preload"; as="font"; type="font/woff2"; crossorigin="anonymous"',
            ],
            $response->headers->all('Link'),
        );
    }

    public function testLimitAppliesToCombinedJsAndFontPreloads()
    {
        $app = new Container;
        $app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://example.com/build/assets/app.js' => [
                    'rel="modulepreload"',
                ],
                'https://example.com/build/assets/inter-400.woff2' => [
                    'rel="preload"',
                    'as="font"',
                ],
                'https://example.com/build/assets/inter-700.woff2' => [
                    'rel="preload"',
                    'as="font"',
                ],
            ];
        });
        Facade::setFacadeApplication($app);

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, fn () => new Response('ok'), 2);

        $this->assertSame(
            [
                '<https://example.com/build/assets/app.js>; rel="modulepreload", <https://example.com/build/assets/inter-400.woff2>; rel="preload"; as="font"',
            ],
            $response->headers->all('Link'),
        );
    }

    public function test_it_can_configure_the_middleware()
    {
        $definition = AddLinkHeadersForPreloadedAssets::using(limit: 5);

        $this->assertSame('Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets:5', $definition);
    }
}
