<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class RouteViewTest extends TestCase
{
    public function testRouteView()
    {
        Route::view('route', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route')->getContent());
        $this->assertSame(200, $this->get('/route')->status());
    }

    public function testRouteViewWithParams()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());

        $this->assertEquals('value1', $this->get('/route/value1/value2')->viewData('param'));
        $this->assertEquals('value2', $this->get('/route/value1/value2')->viewData('param2'));
    }

    public function testRouteViewWithStatus()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame(418, $this->get('/route')->status());
    }

    public function testRouteViewWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418, ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteViewOverloadingStatusWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    /**
     * @dataProvider provideUrlsToValidateEncoding
     *
     * @param  string  $route
     * @param  string  $name
     * @param  string  $act
     * @param  string  $assert
     * @param  array  $payload
     */
    public function testRouteHelperUsingLoopbackIpv6AsDomain(string $route, string $name, string $act, string $assert, array $payload = [])
    {
        Route::get($route, function () use ($name, $payload) {
            return view('route-using-ipv6', ['routeName' => $name, 'payload' => [...$payload]]);
        })->name($name);

        View::addLocation(__DIR__.'/Fixtures');

        $response = $this->get($act);

        $this->assertSame("Test {$assert}", $response->content());
    }

    /**
     * A sets of URLs to test if encoding is match acording with the RFC3986.
     *
     * @see https://github.com/laravel/framework/pull/47802
     * @link http://www.faqs.org/rfcs/rfc3986.html
     *
     * @return array
     *
     * @static
     */
    public static function provideUrlsToValidateEncoding(): array
    {
        return [
            'Ipv6LiteralAddresses' => [
                '/',
                'root',
                'https://[::1]/',
                'https://[::1]',
            ],
            'Ipv6LiteralAddressesWithSquareBracketsInUri' => [
                '/secret[{passphrase}]',
                'square-brackets-secret',
                'https://[::1]/secret[12345678]',
                'https://[::1]/secret%5B12345678%5D',
                ['passphrase' => 12345678],
            ],
            'IPv4LiteralAddressesWithBracketsAndUri' => [
                'foo/{bar}/{baz}',
                'foo-bar-baz',
                'http://127.0.0.1/foo/\'(parenthesis)/"[square_brackets]',
                'http://127.0.0.1/foo/%27%28parenthesis%29/%22%5Bsquare_brackets%5D',
                ['bar' => '\'(parenthesis)', 'baz' => '"[square_brackets]'],
            ],
        ];
    }
}
