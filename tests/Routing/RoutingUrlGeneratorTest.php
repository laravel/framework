<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Routing\UrlRoutable;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class RoutingUrlGeneratorTest extends TestCase
{
    public function testBasicGeneration()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $this->assertEquals('http://www.foo.com/foo/bar', $url->to('foo/bar'));
        $this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar', [], true));
        $this->assertEquals('https://www.foo.com/foo/bar/baz/boom', $url->to('foo/bar', ['baz', 'boom'], true));
        $this->assertEquals('https://www.foo.com/foo/bar/baz?foo=bar', $url->to('foo/bar?foo=bar', ['baz'], true));

        /*
         * Test HTTPS request URL generation...
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('https://www.foo.com/')
        );

        $this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar'));

        /*
         * Test asset URL generation...
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/index.php/')
        );

        $this->assertEquals('http://www.foo.com/foo/bar', $url->asset('foo/bar'));
        $this->assertEquals('https://www.foo.com/foo/bar', $url->asset('foo/bar', true));
    }

    public function testBasicGenerationWithFormatting()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        /*
         * Empty Named Route
         */
        $route = new Route(['GET'], '/named-route', ['as' => 'plain']);
        $routes->add($route);

        $url->formatPathUsing(function ($path) {
            return '/something'.$path;
        });

        $this->assertEquals('http://www.foo.com/something/foo/bar', $url->to('foo/bar'));
        $this->assertEquals('/something/named-route', $url->route('plain', [], false));
    }

    public function testBasicRouteGeneration()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        /*
         * Empty Named Route
         */
        $route = new Route(['GET'], '/', ['as' => 'plain']);
        $routes->add($route);

        /*
         * Named Routes
         */
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo']);
        $routes->add($route);

        /*
         * Parameters...
         */
        $route = new Route(['GET'], 'foo/bar/{baz}/breeze/{boom}', ['as' => 'bar']);
        $routes->add($route);

        /*
         * Single Parameter...
         */
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'foobar']);
        $routes->add($route);

        /*
         * HTTPS...
         */
        $route = new Route(['GET'], 'foo/baz', ['as' => 'baz', 'https']);
        $routes->add($route);

        /*
         * Controller Route Route
         */
        $route = new Route(['GET'], 'foo/bam', ['controller' => 'foo@bar']);
        $routes->add($route);

        /*
         * Non ASCII routes
         */
        $route = new Route(['GET'], 'foo/bar/åαф/{baz}', ['as' => 'foobarbaz']);
        $routes->add($route);

        /*
         * Fragments
         */
        $route = new Route(['GET'], 'foo/bar#derp', ['as' => 'fragment']);
        $routes->add($route);

        /*
         * Invoke action
         */
        $route = new Route(['GET'], 'foo/invoke', ['controller' => 'InvokableActionStub']);
        $routes->add($route);

        /*
         * With Default Parameter
         */
        $url->defaults(['locale' => 'en']);
        $route = new Route(['GET'], 'foo', ['as' => 'defaults', 'domain' => '{locale}.example.com', function () {
        }]);
        $routes->add($route);

        $this->assertEquals('/', $url->route('plain', [], false));
        $this->assertEquals('/?foo=bar', $url->route('plain', ['foo' => 'bar'], false));
        $this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
        $this->assertEquals('/foo/bar', $url->route('foo', [], false));
        $this->assertEquals('/foo/bar?foo=bar', $url->route('foo', ['foo' => 'bar'], false));
        $this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', ['taylor', 'otwell', 'fly' => 'wall']));
        $this->assertEquals('http://www.foo.com/foo/bar/otwell/breeze/taylor?fly=wall', $url->route('bar', ['boom' => 'taylor', 'baz' => 'otwell', 'fly' => 'wall']));
        $this->assertEquals('http://www.foo.com/foo/bar/2', $url->route('foobar', 2));
        $this->assertEquals('http://www.foo.com/foo/bar/taylor', $url->route('foobar', 'taylor'));
        $this->assertEquals('/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', ['taylor', 'otwell', 'fly' => 'wall'], false));
        $this->assertEquals('https://www.foo.com/foo/baz', $url->route('baz'));
        $this->assertEquals('http://www.foo.com/foo/bam', $url->action('foo@bar'));
        $this->assertEquals('http://www.foo.com/foo/invoke', $url->action('InvokableActionStub'));
        $this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', ['wall', 'woz', 'boom' => 'otwell', 'baz' => 'taylor']));
        $this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', ['taylor', 'otwell', 'wall', 'woz']));
        $this->assertEquals('http://www.foo.com/foo/bar/%C3%A5%CE%B1%D1%84/%C3%A5%CE%B1%D1%84', $url->route('foobarbaz', ['baz' => 'åαф']));
        $this->assertEquals('/foo/bar#derp', $url->route('fragment', [], false));
        $this->assertEquals('/foo/bar?foo=bar#derp', $url->route('fragment', ['foo' => 'bar'], false));
        $this->assertEquals('/foo/bar?baz=%C3%A5%CE%B1%D1%84#derp', $url->route('fragment', ['baz' => 'åαф'], false));
        $this->assertEquals('http://en.example.com/foo', $url->route('defaults'));
    }

    public function testFluentRouteNameDefinitions()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        /*
         * Named Routes
         */
        $route = new Route(['GET'], 'foo/bar', []);
        $route->name('foo');
        $routes->add($route);
        $routes->refreshNameLookups();

        $this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testControllerRoutesWithADefaultNamespace()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $url->setRootControllerNamespace('namespace');

        /*
         * Controller Route Route
         */
        $route = new Route(['GET'], 'foo/bar', ['controller' => 'namespace\foo@bar']);
        $routes->add($route);

        $route = new Route(['GET'], 'something/else', ['controller' => 'something\foo@bar']);
        $routes->add($route);

        $route = new Route(['GET'], 'foo/invoke', ['controller' => 'namespace\InvokableActionStub']);
        $routes->add($route);

        $this->assertEquals('http://www.foo.com/foo/bar', $url->action('foo@bar'));
        $this->assertEquals('http://www.foo.com/something/else', $url->action('\something\foo@bar'));
        $this->assertEquals('http://www.foo.com/foo/invoke', $url->action('InvokableActionStub'));
    }

    public function testControllerRoutesOutsideOfDefaultNamespace()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $url->setRootControllerNamespace('namespace');

        $route = new Route(['GET'], 'root/namespace', ['controller' => '\root\namespace@foo']);
        $routes->add($route);

        $route = new Route(['GET'], 'invokable/namespace', ['controller' => '\root\namespace\InvokableActionStub']);
        $routes->add($route);

        $this->assertEquals('http://www.foo.com/root/namespace', $url->action('\root\namespace@foo'));
        $this->assertEquals('http://www.foo.com/invokable/namespace', $url->action('\root\namespace\InvokableActionStub'));
    }

    public function testRoutableInterfaceRouting()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar}', ['as' => 'routable']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertEquals('/foo/routable', $url->route('routable', [$model], false));
    }

    public function testRoutableInterfaceRoutingWithSingleParameter()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar}', ['as' => 'routable']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertEquals('/foo/routable', $url->route('routable', $model, false));
    }

    public function testRoutesMaintainRequestScheme()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('https://www.foo.com/')
        );

        /*
         * Named Routes
         */
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo']);
        $routes->add($route);

        $this->assertEquals('https://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testHttpOnlyRoutes()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('https://www.foo.com/')
        );

        /*
         * Named Routes
         */
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'http']);
        $routes->add($route);

        $this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testRoutesWithDomains()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        /*
         * Wildcards & Domains...
         */
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'bar', 'domain' => 'sub.{foo}.com']);
        $routes->add($route);

        $this->assertEquals('http://sub.foo.com/foo/bar', $url->route('foo'));
        $this->assertEquals('http://sub.taylor.com/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell']));
        $this->assertEquals('/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell'], false));
    }

    public function testRoutesWithDomainsAndPorts()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com:8080/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        /*
         * Wildcards & Domains...
         */
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'bar', 'domain' => 'sub.{foo}.com']);
        $routes->add($route);

        $this->assertEquals('http://sub.foo.com:8080/foo/bar', $url->route('foo'));
        $this->assertEquals('http://sub.taylor.com:8080/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell']));
    }

    public function testRoutesWithDomainsStripsProtocols()
    {
        /*
         * http:// Route
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'http://sub.foo.com']);
        $routes->add($route);

        $this->assertEquals('http://sub.foo.com/foo/bar', $url->route('foo'));

        /*
         * https:// Route
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('https://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'https://sub.foo.com']);
        $routes->add($route);

        $this->assertEquals('https://sub.foo.com/foo/bar', $url->route('foo'));
    }

    public function testHttpsRoutesWithDomains()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('https://foo.com/')
        );

        /*
         * When on HTTPS, no need to specify 443
         */
        $route = new Route(['GET'], 'foo/bar', ['as' => 'baz', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        $this->assertEquals('https://sub.foo.com/foo/bar', $url->route('baz'));
    }

    public function testRoutesWithDomainsThroughProxy()
    {
        if (defined(SymfonyRequest::class.'::HEADER_X_FORWARDED_ALL')) {
            Request::setTrustedProxies(['10.0.0.1'], SymfonyRequest::HEADER_X_FORWARDED_ALL);
        } else {
            Request::setTrustedProxies(['10.0.0.1']);
        }

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_PORT' => '80'])
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        $this->assertEquals('http://sub.foo.com/foo/bar', $url->route('foo'));
    }

    /**
     * @expectedException \Illuminate\Routing\Exceptions\UrlGenerationException
     */
    public function testUrlGenerationForControllersRequiresPassingOfRequiredParameters()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com:8080/')
        );

        $route = new Route(['GET'], 'foo/{one}/{two?}/{three?}', ['as' => 'foo', function () {
        }]);
        $routes->add($route);

        $this->assertEquals('http://www.foo.com:8080/foo', $url->route('foo'));
    }

    public function testForceRootUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $url->forceRootUrl('https://www.bar.com');
        $this->assertEquals('http://www.bar.com/foo/bar', $url->to('foo/bar'));

        // Ensure trailing slash is trimmed from root URL as UrlGenerator already handles this
        $url->forceRootUrl('http://www.foo.com/');
        $this->assertEquals('http://www.foo.com/bar', $url->to('/bar'));

        /*
         * Route Based...
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $url->forceScheme('https');
        $route = new Route(['GET'], '/foo', ['as' => 'plain']);
        $routes->add($route);

        $this->assertEquals('https://www.foo.com/foo', $url->route('plain'));

        $url->forceRootUrl('https://www.bar.com');
        $this->assertEquals('https://www.bar.com/foo', $url->route('plain'));
    }

    public function testPrevious()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );

        $url->getRequest()->headers->set('referer', 'http://www.bar.com/');
        $this->assertEquals('http://www.bar.com/', $url->previous());

        $url->getRequest()->headers->remove('referer');
        $this->assertEquals($url->to('/'), $url->previous());

        $this->assertEquals($url->to('/foo'), $url->previous('/foo'));
    }
}

class RoutableInterfaceStub implements UrlRoutable
{
    public $key;

    public function getRouteKey()
    {
        return $this->{$this->getRouteKeyName()};
    }

    public function getRouteKeyName()
    {
        return 'key';
    }
}

class InvokableActionStub
{
    public function __invoke()
    {
        return 'hello';
    }
}
