<?php

namespace Illuminate\Tests\Integration\Http\Middleware;

use Illuminate\Foundation\Vite;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Tests\Integration\TestCase;

class VitePreloadingTest extends TestCase
{
    public function testItDoesNotSetLinkTagWhenNoTagsHaveBeenPreloaded()
    {
        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertNull($response->headers->get('Link'));
    }

    public function testItAddsPreloadLinkHeader()
    {
        $this->app->instance(Vite::class, new class extends Vite
        {
            protected $preloadedAssets = [
                'https://laravel.com/app.js' => [
                    'rel="modulepreload"',
                    'foo="bar"',
                ],
            ];
        });

        $response = (new AddLinkHeadersForPreloadedAssets)->handle(new Request, function () {
            return new Response('Hello Laravel');
        });

        $this->assertSame(
            $response->headers->get('Link'),
            '<https://laravel.com/app.js>; rel="modulepreload"; foo="bar"'
        );
    }
}
