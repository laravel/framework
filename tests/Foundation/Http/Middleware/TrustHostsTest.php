<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;

class TrustHostsTest extends TestCase
{
    /**
     * @expectedException  Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function testToMethodThrowExceptionForUntrustedHosts()
    {
        $trustedHosts = ['laravel.com'];
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $urlGenerator->to('/');
    }

    /**
     * @expectedException  Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function testToMethodThrowExceptionForUntrustedServerName()
    {
        $trustedHosts = ['laravel.com'];
        $headers = [];
        $servers = ['SERVER_NAME' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    /**
     * @expectedException  Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function testToMethodThrowExceptionForUntrustedServerAddr()
    {
        $trustedHosts = ['laravel.com'];
        $headers = [];
        $servers = ['SERVER_ADDR' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $urlGenerator->to('/');
    }

    /**
     * @expectedException  Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException
     */
    public function testRouteMethodThrowExceptionForUntrustedHosts()
    {
        $trustedHosts = ['laravel.com'];
        $headers = ['HOST' => 'malicious.com'];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers);
        $urlGenerator->route('foo_index');
    }

    public function testItDoesNotThrowExceptionForTrustedHost()
    {
        $trustedHosts = ['laravel.com'];
        $headers = ['HOST' => 'laravel.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $this->assertEquals("http://laravel.com", $urlGenerator->to('/'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage trusted hosts are not set.
     */
    public function testItThrowExceptionWhenNoTrustedHostsAreSpecified()
    {
        $trustedHosts = [];
        $headers = ['HOST' => 'laravel.com'];
        $servers = [];
        $urlGenerator = $this->createUrlGenerator($trustedHosts, $headers, $servers);
        $this->assertEquals("http://laravel.com", $urlGenerator->to('/'));
    }

    protected function createUrlGenerator($trustedHosts = array(), $headers = array(), $servers = array())
    {
        $middleware = new TrustHosts;
        $middleware->setTrustedHosts($trustedHosts);

        $request = new Request;

        foreach ($headers as $key => $val) {
            $request->headers->set($key, $val);
        }

        foreach ($servers as $key => $val) {
            $request->server->set($key, $val);
        }

        $middleware->handle($request, function () {});

        $routes = new RouteCollection;
        $routes->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        return new UrlGenerator($routes, $request);
    }
}
