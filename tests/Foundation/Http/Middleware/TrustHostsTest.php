<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;
use Illuminate\Foundation\Http\Middleware\TrustHosts;

class TrustHostsTest extends TestCase
{
    protected static $orignalTrustHosts;

    public static function setUpBeforeClass(){
        self::$orignalTrustHosts = Request::getTrustedHosts();
    }

    public static function tearDownAfterClass(){
        Request::setTrustedHosts(self::$orignalTrustHosts);
    }

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
        $this->assertEquals('http://laravel.com', $urlGenerator->to('/'));
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
        $this->assertEquals('http://laravel.com', $urlGenerator->to('/'));
    }

    protected function createUrlGenerator($trustedHosts = [], $headers = [], $servers = [])
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

        $middleware->handle($request, function () {
        });

        $routes = new RouteCollection;
        $routes->add(new Route('GET', 'foo', [
            'uses' => 'FooController@index',
            'as' => 'foo_index',
        ]));

        return new UrlGenerator($routes, $request);
    }
}
