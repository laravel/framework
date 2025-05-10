<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

include_once 'Enums.php';

class RoutingUrlGeneratorTest extends TestCase
{
    public function testBasicGeneration()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $this->assertSame('http://www.foo.com/foo/bar', $url->to('foo/bar'));
        $this->assertSame('https://www.foo.com/foo/bar', $url->to('foo/bar', [], true));
        $this->assertSame('https://www.foo.com/foo/bar/baz/boom', $url->to('foo/bar', ['baz', 'boom'], true));
        $this->assertSame('https://www.foo.com/foo/bar/baz?foo=bar', $url->to('foo/bar?foo=bar', ['baz'], true));

        // Test HTTPS request URL generation...
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $this->assertSame('https://www.foo.com/foo/bar', $url->to('foo/bar'));
    }

    public function testQueryGeneration()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $this->assertSame('http://www.foo.com/foo/bar', $url->query('foo/bar'));
        $this->assertSame('http://www.foo.com/foo/bar?0=foo', $url->query('foo/bar', ['foo']));
        $this->assertSame('http://www.foo.com/foo/bar?baz=boom', $url->query('foo/bar', ['baz' => 'boom']));
        $this->assertSame('http://www.foo.com/foo/bar?baz=zee&zal=bee', $url->query('foo/bar?baz=boom&zal=bee', ['baz' => 'zee']));
        $this->assertSame('http://www.foo.com/foo/bar?zal=bee', $url->query('foo/bar?baz=boom&zal=bee', ['baz' => null]));
        $this->assertSame('http://www.foo.com/foo/bar?baz=boom', $url->query('foo/bar?baz=boom', ['nonexist' => null]));
        $this->assertSame('http://www.foo.com/foo/bar', $url->query('foo/bar?baz=boom', ['baz' => null]));
        $this->assertSame('https://www.foo.com/foo/bar/baz?foo=bar&zal=bee', $url->query('foo/bar?foo=bar', ['zal' => 'bee'], ['baz'], true));
        $this->assertSame('http://www.foo.com/foo/bar?baz[0]=boom&baz[1]=bam&baz[2]=bim', urldecode($url->query('foo/bar', ['baz' => ['boom', 'bam', 'bim']])));
    }

    public function testAssetGeneration()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/index.php/')
        );

        $this->assertSame('http://www.foo.com/foo/bar', $url->asset('foo/bar'));
        $this->assertSame('https://www.foo.com/foo/bar', $url->asset('foo/bar', true));

        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/index.php/'),
            '/'
        );

        $this->assertSame('/foo/bar', $url->asset('foo/bar'));
        $this->assertSame('/foo/bar', $url->asset('foo/bar', true));
    }

    public function testBasicGenerationWithHostFormatting()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], '/named-route', ['as' => 'plain']);
        $routes->add($route);

        $url->formatHostUsing(function ($host) {
            return str_replace('foo.com', 'foo.org', $host);
        });

        $this->assertSame('http://www.foo.org/foo/bar', $url->to('foo/bar'));
        $this->assertSame('/named-route', $url->route('plain', [], false));
    }

    public function testBasicGenerationWithRequestBaseUrlWithSubfolder()
    {
        $request = Request::create('http://www.foo.com/subfolder/foo/bar/subfolder/');

        $request->server->set('SCRIPT_FILENAME', '/var/www/laravel-project/public/subfolder/index.php');
        $request->server->set('PHP_SELF', '/subfolder/index.php');

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request
        );

        $route = new Route(['GET'], 'foo/bar/subfolder', ['as' => 'foobar']);
        $routes->add($route);

        $this->assertSame('/subfolder', $request->getBaseUrl());
        $this->assertSame('/foo/bar/subfolder', $url->route('foobar', [], false));
    }

    public function testBasicGenerationWithRequestBaseUrlWithSubfolderAndFileSuffix()
    {
        $request = Request::create('http://www.foo.com/subfolder/index.php');

        $request->server->set('SCRIPT_FILENAME', '/var/www/laravel-project/public/subfolder/index.php');
        $request->server->set('PHP_SELF', '/subfolder/index.php');

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request
        );

        $route = new Route(['GET'], 'foo/bar/subfolder', ['as' => 'foobar']);
        $routes->add($route);

        $this->assertSame('/subfolder', $request->getBasePath());
        $this->assertSame('/subfolder/index.php', $request->getBaseUrl());
        $this->assertSame('/foo/bar/subfolder', $url->route('foobar', [], false));
    }

    public function testBasicGenerationWithRequestBaseUrlWithFileSuffix()
    {
        $request = Request::create('http://www.foo.com/other.php');

        $request->server->set('SCRIPT_FILENAME', '/var/www/laravel-project/public/other.php');
        $request->server->set('PHP_SELF', '/other.php');

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request
        );

        $route = new Route(['GET'], 'foo/bar/subfolder', ['as' => 'foobar']);
        $routes->add($route);

        $this->assertSame('', $request->getBasePath());
        $this->assertSame('/other.php', $request->getBaseUrl());
        $this->assertSame('/foo/bar/subfolder', $url->route('foobar', [], false));
    }

    public function testBasicGenerationWithPathFormatting()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], '/named-route', ['as' => 'plain']);
        $routes->add($route);

        $url->formatPathUsing(function ($path) {
            return '/something'.$path;
        });

        $this->assertSame('http://www.foo.com/something/foo/bar', $url->to('foo/bar'));
        $this->assertSame('/something/named-route', $url->route('plain', [], false));
    }

    public function testUrlFormattersShouldReceiveTargetRoute()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://abc.com/')
        );

        $namedRoute = new Route(['GET'], '/bar', ['as' => 'plain', 'root' => 'bar.com', 'path' => 'foo']);
        $routes->add($namedRoute);

        $url->formatHostUsing(function ($root, $route) {
            return $route ? 'http://'.$route->getAction('root') : $root;
        });

        $url->formatPathUsing(function ($path, $route) {
            return $route ? '/'.$route->getAction('path') : $path;
        });

        $this->assertSame('http://abc.com/foo/bar', $url->to('foo/bar'));
        $this->assertSame('http://bar.com/foo', $url->route('plain'));
    }

    public function testBasicRouteGeneration()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        // Empty Named Route
        $route = new Route(['GET'], '/', ['as' => 'plain']);
        $routes->add($route);

        // Named Routes
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo']);
        $routes->add($route);

        // Parameters...
        $route = new Route(['GET'], 'foo/bar/{baz}/breeze/{boom}', ['as' => 'bar']);
        $routes->add($route);

        // Single Parameter...
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'foobar']);
        $routes->add($route);

        // Optional parameter
        $route = new Route(['GET'], 'foo/bar/{baz?}', ['as' => 'optional']);
        $routes->add($route);

        // HTTPS...
        $route = new Route(['GET'], 'foo/baz', ['as' => 'baz', 'https']);
        $routes->add($route);

        // Controller Route Route
        $route = new Route(['GET'], 'foo/bam', ['controller' => 'foo@bar']);
        $routes->add($route);

        // Non ASCII routes
        $route = new Route(['GET'], 'foo/bar/åαф/{baz}', ['as' => 'foobarbaz']);
        $routes->add($route);

        // Fragments
        $route = new Route(['GET'], 'foo/bar#derp', ['as' => 'fragment']);
        $routes->add($route);

        // Invoke action
        $route = new Route(['GET'], 'foo/invoke', ['controller' => 'InvokableActionStub']);
        $routes->add($route);

        // With Default Parameter
        $url->defaults(['locale' => 'en']);
        $route = new Route(['GET'], 'foo', ['as' => 'defaults', 'domain' => '{locale}.example.com', function () {
            //
        }]);
        $routes->add($route);

        // With backed enum name and domain
        $route = (new Route(['GET'], 'backed-enum', ['as' => 'prefixed.']))->name(RouteNameEnum::UserIndex)->domain(RouteDomainEnum::DashboardDomain);
        $routes->add($route);

        $this->assertSame('/', $url->route('plain', [], false));
        $this->assertSame('/?foo=bar', $url->route('plain', ['foo' => 'bar'], false));
        $this->assertSame('http://www.foo.com/foo/bar', $url->route('foo'));
        $this->assertSame('/foo/bar', $url->route('foo', [], false));
        $this->assertSame('/foo/bar?foo=bar', $url->route('foo', ['foo' => 'bar'], false));
        $this->assertSame('http://www.foo.com/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', ['taylor', 'otwell', 'fly' => 'wall']));
        $this->assertSame('http://www.foo.com/foo/bar/otwell/breeze/taylor?fly=wall', $url->route('bar', ['boom' => 'taylor', 'baz' => 'otwell', 'fly' => 'wall']));
        $this->assertSame('http://www.foo.com/foo/bar/0', $url->route('foobar', 0));
        $this->assertSame('http://www.foo.com/foo/bar/2', $url->route('foobar', 2));
        $this->assertSame('http://www.foo.com/foo/bar/taylor', $url->route('foobar', 'taylor'));
        $this->assertSame('/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', ['taylor', 'otwell', 'fly' => 'wall'], false));
        $this->assertSame('https://www.foo.com/foo/baz', $url->route('baz'));
        $this->assertSame('http://www.foo.com/foo/bam', $url->action('foo@bar'));
        $this->assertSame('http://www.foo.com/foo/bam', $url->action(['foo', 'bar']));
        $this->assertSame('http://www.foo.com/foo/invoke', $url->action('InvokableActionStub'));
        $this->assertSame('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', ['wall', 'woz', 'boom' => 'otwell', 'baz' => 'taylor']));
        $this->assertSame('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', ['taylor', 'otwell', 'wall', 'woz']));
        $this->assertSame('http://www.foo.com/foo/bar', $url->route('optional'));
        $this->assertSame('http://www.foo.com/foo/bar', $url->route('optional', ['baz' => null]));
        $this->assertSame('http://www.foo.com/foo/bar', $url->route('optional', ['baz' => '']));
        $this->assertSame('http://www.foo.com/foo/bar/0', $url->route('optional', ['baz' => 0]));
        $this->assertSame('http://www.foo.com/foo/bar/taylor', $url->route('optional', 'taylor'));
        $this->assertSame('http://www.foo.com/foo/bar/taylor', $url->route('optional', ['taylor']));
        $this->assertSame('http://www.foo.com/foo/bar/taylor?breeze', $url->route('optional', ['taylor', 'breeze']));
        $this->assertSame('http://www.foo.com/foo/bar/taylor?wall=woz', $url->route('optional', ['wall' => 'woz', 'taylor']));
        $this->assertSame('http://www.foo.com/foo/bar/taylor?wall=woz&breeze', $url->route('optional', ['wall' => 'woz', 'breeze', 'baz' => 'taylor']));
        $this->assertSame('http://www.foo.com/foo/bar?wall=woz', $url->route('optional', ['wall' => 'woz']));
        $this->assertSame('http://www.foo.com/foo/bar/%C3%A5%CE%B1%D1%84/%C3%A5%CE%B1%D1%84', $url->route('foobarbaz', ['baz' => 'åαф']));
        $this->assertSame('/foo/bar#derp', $url->route('fragment', [], false));
        $this->assertSame('/foo/bar?foo=bar#derp', $url->route('fragment', ['foo' => 'bar'], false));
        $this->assertSame('/foo/bar?baz=%C3%A5%CE%B1%D1%84#derp', $url->route('fragment', ['baz' => 'åαф'], false));
        $this->assertSame('http://en.example.com/foo', $url->route('defaults'));
        $this->assertSame('http://dashboard.myapp.com/backed-enum', $url->route('prefixed.users.index'));
    }

    public function testFluentRouteNameDefinitions()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        // Named Routes
        $route = new Route(['GET'], 'foo/bar', []);
        $route->name('foo');
        $routes->add($route);
        $routes->refreshNameLookups();

        $this->assertSame('http://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testControllerRoutesWithADefaultNamespace()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->setRootControllerNamespace('namespace');

        // Controller Route Route
        $route = new Route(['GET'], 'foo/bar', ['controller' => 'namespace\foo@bar']);
        $routes->add($route);

        $route = new Route(['GET'], 'something/else', ['controller' => 'something\foo@bar']);
        $routes->add($route);

        $route = new Route(['GET'], 'foo/invoke', ['controller' => 'namespace\InvokableActionStub']);
        $routes->add($route);

        $this->assertSame('http://www.foo.com/foo/bar', $url->action('foo@bar'));
        $this->assertSame('http://www.foo.com/something/else', $url->action('\something\foo@bar'));
        $this->assertSame('http://www.foo.com/foo/invoke', $url->action('InvokableActionStub'));
    }

    public function testControllerRoutesOutsideOfDefaultNamespace()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->setRootControllerNamespace('namespace');

        $route = new Route(['GET'], 'root/namespace', ['controller' => '\root\namespace@foo']);
        $routes->add($route);

        $route = new Route(['GET'], 'invokable/namespace', ['controller' => '\root\namespace\InvokableActionStub']);
        $routes->add($route);

        $this->assertSame('http://www.foo.com/root/namespace', $url->action('\root\namespace@foo'));
        $this->assertSame('http://www.foo.com/invokable/namespace', $url->action('\root\namespace\InvokableActionStub'));
    }

    public function testRoutableInterfaceRouting()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar}', ['as' => 'routable']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertSame('/foo/routable', $url->route('routable', [$model], false));
    }

    public function testRoutableInterfaceRoutingWithCustomBindingField()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar:slug}', ['as' => 'routable']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertSame('/foo/test-slug', $url->route('routable', ['bar' => $model], false));
        $this->assertSame('/foo/test-slug', $url->route('routable', [$model], false));
    }

    public function testRoutableInterfaceRoutingAsQueryString()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo', ['as' => 'query-string']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertSame('/foo?routable', $url->route('query-string', $model, false));
        $this->assertSame('/foo?routable', $url->route('query-string', [$model], false));
        $this->assertSame('/foo?foo=routable', $url->route('query-string', ['foo' => $model], false));
    }

    public function testRoutableInterfaceRoutingWithSeparateBindingFieldOnlyForSecondParameter()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar}/{baz:slug}', ['as' => 'routable']);
        $routes->add($route);

        $model1 = new RoutableInterfaceStub;
        $model1->key = 'routable-1';

        $model2 = new RoutableInterfaceStub;
        $model2->key = 'routable-2';

        $this->assertSame('/foo/routable-1/test-slug', $url->route('routable', ['bar' => $model1, 'baz' => $model2], false));
        $this->assertSame('/foo/routable-1/test-slug', $url->route('routable', [$model1, $model2], false));
    }

    public function testRoutableInterfaceRoutingWithSingleParameter()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/{bar}', ['as' => 'routable']);
        $routes->add($route);

        $model = new RoutableInterfaceStub;
        $model->key = 'routable';

        $this->assertSame('/foo/routable', $url->route('routable', $model, false));
    }

    public function testRoutesMaintainRequestScheme()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        // Named Routes
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo']);
        $routes->add($route);

        $this->assertSame('https://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testHttpOnlyRoutes()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        // Named Routes
        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'http']);
        $routes->add($route);

        $this->assertSame('http://www.foo.com/foo/bar', $url->route('foo'));
    }

    public function testRoutesWithDomains()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        // Wildcards & Domains...
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'bar', 'domain' => 'sub.{foo}.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com/foo/bar', $url->route('foo'));
        $this->assertSame('http://sub.taylor.com/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell']));
        $this->assertSame('/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell'], false));
    }

    public function testRoutesWithDomainsAndPorts()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com:8080/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        // Wildcards & Domains...
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'bar', 'domain' => 'sub.{foo}.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com:8080/foo/bar', $url->route('foo'));
        $this->assertSame('http://sub.taylor.com:8080/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell']));
    }

    public function testRoutesWithDomainsStripsProtocols()
    {
        // http:// Route
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'http://sub.foo.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com/foo/bar', $url->route('foo'));

        // https:// Route
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'https://sub.foo.com']);
        $routes->add($route);

        $this->assertSame('https://sub.foo.com/foo/bar', $url->route('foo'));
    }

    public function testHttpsRoutesWithDomains()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://foo.com/')
        );

        // When on HTTPS, no need to specify 443
        $route = new Route(['GET'], 'foo/bar', ['as' => 'baz', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        $this->assertSame('https://sub.foo.com/foo/bar', $url->route('baz'));
    }

    public function testRoutesWithDomainsThroughProxy()
    {
        Request::setTrustedProxies(['10.0.0.1'], SymfonyRequest::HEADER_X_FORWARDED_FOR | SymfonyRequest::HEADER_X_FORWARDED_HOST | SymfonyRequest::HEADER_X_FORWARDED_PORT | SymfonyRequest::HEADER_X_FORWARDED_PROTO);

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_PORT' => '80'])
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'sub.foo.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com/foo/bar', $url->route('foo'));
    }

    public static function providerRouteParameters()
    {
        return [
            [['test' => 123]],
            [['one' => null, 'test' => 123]],
            [['one' => '', 'test' => 123]],
        ];
    }

    #[DataProvider('providerRouteParameters')]
    public function testUrlGenerationForControllersRequiresPassingOfRequiredParameters($parameters)
    {
        $this->expectException(UrlGenerationException::class);

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com:8080/')
        );

        $route = new Route(['GET'], 'foo/{one}/{two?}/{three?}', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $this->assertSame('http://www.foo.com:8080/foo?test=123', $url->route('foo', $parameters));
    }

    public static function provideParametersAndExpectedMeaningfulExceptionMessages()
    {
        return [
            'Missing parameters "one", "two" and "three"' => [
                [],
                'Missing required parameters for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameters: one, two, three].',
            ],
            'Missing parameters "two" and "three"' => [
                ['one' => '123'],
                'Missing required parameters for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameters: two, three].',
            ],
            'Missing parameters "one" and "three"' => [
                ['two' => '123'],
                'Missing required parameters for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameters: one, three].',
            ],
            'Missing parameters "one" and "two"' => [
                ['three' => '123'],
                'Missing required parameters for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameters: one, two].',
            ],
            'Missing parameter "three"' => [
                ['one' => '123', 'two' => '123'],
                'Missing required parameter for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameter: three].',
            ],
            'Missing parameter "two"' => [
                ['one' => '123', 'three' => '123'],
                'Missing required parameter for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameter: two].',
            ],
            'Missing parameter "one"' => [
                ['two' => '123', 'three' => '123'],
                'Missing required parameter for [Route: foo] [URI: foo/{one}/{two}/{three}/{four?}] [Missing parameter: one].',
            ],
        ];
    }

    #[DataProvider('provideParametersAndExpectedMeaningfulExceptionMessages')]
    public function testUrlGenerationThrowsExceptionForMissingParametersWithMeaningfulMessage($parameters, $expectedMeaningfulExceptionMessage)
    {
        $this->expectException(UrlGenerationException::class);
        $this->expectExceptionMessage($expectedMeaningfulExceptionMessage);

        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com:8080/')
        );

        $route = new Route(['GET'], 'foo/{one}/{two}/{three}/{four?}', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $url->route('foo', $parameters);
    }

    public function testSetAssetUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->useOrigin('https://www.bar.com');
        $this->assertSame('http://www.bar.com/foo/bar', $url->to('foo/bar'));
        $this->assertSame('http://www.bar.com/foo/bar', $url->asset('foo/bar'));

        $url->useAssetOrigin('https://www.foo.com');
        $this->assertNotSame('https://www.foo.com/foo/bar', $url->to('foo/bar'));
        $this->assertSame('https://www.foo.com/foo/bar', $url->asset('foo/bar'));
        $this->assertSame('https://www.foo.com/foo/bar', $url->asset('foo/bar', true));
        $this->assertSame('https://www.foo.com/foo/bar', $url->asset('foo/bar', false));
    }

    public function testUseRootUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->useOrigin('https://www.bar.com');
        $this->assertSame('http://www.bar.com/foo/bar', $url->to('foo/bar'));

        // Ensure trailing slash is trimmed from root URL as UrlGenerator already handles this
        $url->useOrigin('http://www.foo.com/');
        $this->assertSame('http://www.foo.com/bar', $url->to('/bar'));

        // Route Based...
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->forceScheme('https');
        $route = new Route(['GET'], '/foo', ['as' => 'plain']);
        $routes->add($route);

        $this->assertSame('https://www.foo.com/foo', $url->route('plain'));

        $url->useOrigin('https://www.bar.com');
        $this->assertSame('https://www.bar.com/foo', $url->route('plain'));
    }

    public function testForceHttps()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->forceHttps();
        $route = new Route(['GET'], '/foo', ['as' => 'plain']);
        $routes->add($route);

        $this->assertSame('https://www.foo.com/foo', $url->route('plain'));
    }

    public function testPrevious()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->getRequest()->headers->set('referer', 'http://www.bar.com/');
        $this->assertSame('http://www.bar.com/', $url->previous());

        $url->getRequest()->headers->remove('referer');
        $this->assertEquals($url->to('/'), $url->previous());

        $this->assertEquals($url->to('/foo'), $url->previous('/foo'));
    }

    public function testPreviousPath()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->getRequest()->headers->set('referer', 'http://www.foo.com?baz=bah');
        $this->assertSame('/', $url->previousPath());

        $url->getRequest()->headers->set('referer', 'http://www.foo.com/?baz=bah');
        $this->assertSame('/', $url->previousPath());

        $url->getRequest()->headers->set('referer', 'http://www.foo.com/bar?baz=bah');
        $this->assertSame('/bar', $url->previousPath());

        $url->getRequest()->headers->remove('referer');
        $this->assertSame('/', $url->previousPath());

        $this->assertSame('/bar', $url->previousPath('/bar'));
    }

    public function testRouteNotDefinedException()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route [not_exists_route] not defined.');

        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->route('not_exists_route');
    }

    public function testSignedUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'secret';
        });

        $route = new Route(['GET'], 'foo', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $request = Request::create($url->signedRoute('foo'));

        $this->assertTrue($url->hasValidSignature($request));

        $request = Request::create($url->signedRoute('foo').'?tampered=true');

        $this->assertFalse($url->hasValidSignature($request));

        $request = Request::create($url->signedRoute('foo').'&tampered=true');

        $this->assertTrue($url->hasValidSignature($request, ignoreQuery: ['tampered']));

        $this->assertTrue($url->hasValidSignature($request, ignoreQuery: fn ($parameter) => $parameter === 'tampered'));
    }

    public function testSignedUrlImplicitModelBinding()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'secret';
        });

        $route = new Route(['GET'], 'foo/{user:uuid}', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $user = new RoutingUrlGeneratorTestUser(['uuid' => '0231d4ac-e9e3-4452-a89a-4427cfb23c3e']);

        $request = Request::create($url->signedRoute('foo', $user));

        $this->assertTrue($url->hasValidSignature($request));
    }

    public function testSignedRelativeUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'secret';
        });

        $route = new Route(['GET'], 'foo', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $result = $url->signedRoute('foo', [], null, false);

        $request = Request::create($result);

        $this->assertTrue($url->hasValidSignature($request, false));

        $request = Request::create($url->signedRoute('foo', [], null, false).'?tampered=true');

        $this->assertFalse($url->hasValidSignature($request, false));
    }

    public function testSignedUrlParameterCannotBeNamedSignature()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'secret';
        });

        $route = new Route(['GET'], 'foo/{signature}', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        Request::create($url->signedRoute('foo', ['signature' => 'bar']));
    }

    public function testSignedUrlParameterCannotBeNamedExpires()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'secret';
        });

        $route = new Route(['GET'], 'foo/{expires}', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        Request::create($url->signedRoute('foo', ['expires' => 253402300799]));
    }

    public function testRouteGenerationWithBackedEnums()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $namedRoute = new Route(['GET'], '/foo/{bar}', ['as' => 'foo.bar']);
        $routes->add($namedRoute);

        $this->assertSame('http://www.foo.com/foo/fruits', $url->route('foo.bar', CategoryBackedEnum::Fruits));
    }

    public function testRouteGenerationWithNestedBackedEnums()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $namedRoute = new Route(['GET'], '/foo', ['as' => 'foo']);
        $routes->add($namedRoute);

        $this->assertSame(
            'http://www.foo.com/foo?filter%5B0%5D=people&filter%5B1%5D=fruits',
            $url->route('foo', ['filter' => [CategoryBackedEnum::People, CategoryBackedEnum::Fruits]]),
        );
    }

    public function testSignedUrlWithKeyResolver()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            $request = Request::create('http://www.foo.com/')
        );
        $url->setKeyResolver(function () {
            return 'first-secret';
        });

        $route = new Route(['GET'], 'foo', ['as' => 'foo', function () {
            //
        }]);
        $routes->add($route);

        $firstRequest = Request::create($url->signedRoute('foo'));

        $this->assertTrue($url->hasValidSignature($firstRequest));

        $request = Request::create($url->signedRoute('foo').'?tampered=true');

        $this->assertFalse($url->hasValidSignature($request));

        $url2 = $url->withKeyResolver(function () {
            return 'second-secret';
        });

        $this->assertFalse($url2->hasValidSignature($firstRequest));

        $secondRequest = Request::create($url2->signedRoute('foo'));

        $this->assertTrue($url2->hasValidSignature($secondRequest));
        $this->assertFalse($url->hasValidSignature($secondRequest));

        // Key resolver also supports multiple keys, for app key rotation via the config "app.previous_keys"
        $url3 = $url->withKeyResolver(function () {
            return ['first-secret', 'second-secret'];
        });

        $this->assertTrue($url3->hasValidSignature($firstRequest));
        $this->assertTrue($url3->hasValidSignature($secondRequest));
    }

    public function testMissingNamedRouteResolution()
    {
        $url = new UrlGenerator(
            new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->resolveMissingNamedRoutesUsing(fn ($name, $parameters, $absolute) => 'test-url');

        $this->assertSame('test-url', $url->route('foo'));
    }

    public function testPassedParametersHavePrecedenceOverDefaults()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $url->defaults([
            'tenant' => 'defaultTenant',
        ]);

        $route = new Route(['GET'], 'bar/{tenant}/{post}', ['as' => 'bar', fn () => '']);
        $routes->add($route);

        // Named parameters
        $this->assertSame(
            'https://www.foo.com/bar/concreteTenant/concretePost',
            $url->route('bar', [
                'tenant' => tap(new RoutableInterfaceStub, fn ($x) => $x->key = 'concreteTenant'),
                'post' => tap(new RoutableInterfaceStub, fn ($x) => $x->key = 'concretePost'),
            ]),
        );

        // Positional parameters
        $this->assertSame(
            'https://www.foo.com/bar/concreteTenant/concretePost',
            $url->route('bar', [
                tap(new RoutableInterfaceStub, fn ($x) => $x->key = 'concreteTenant'),
                tap(new RoutableInterfaceStub, fn ($x) => $x->key = 'concretePost'),
            ]),
        );
    }

    public function testComplexRouteGenerationWithDefaultsAndBindingFields()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $url->defaults([
            'tenant' => 'defaultTenant',
            'tenant:slug' => 'defaultTenantSlug',
            'team' => 'defaultTeam',
            'team:slug' => 'defaultTeamSlug',
            'user' => 'defaultUser',
            'user:slug' => 'defaultUserSlug',
        ]);

        $keyParam = fn ($value) => tap(new RoutableInterfaceStub, fn ($routable) => $routable->key = $value);
        $slugParam = fn ($value) => tap(new RoutableInterfaceStub, fn ($routable) => $routable->slug = $value);

        /**
         * One parameter with a default value, one without a default value.
         *
         * No binding fields.
         */
        $route = new Route(['GET'], 'tenantPost/{tenant}/{post}', ['as' => 'tenantPost', fn () => '']);
        $routes->add($route);

        // tenantPost: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPost/concreteTenant/concretePost',
            $url->route('tenantPost', [$keyParam('concreteTenant'), $keyParam('concretePost')]),
        );

        // tenantPost: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantPost/concreteTenant/concretePost',
            $url->route('tenantPost', ['tenant' => $keyParam('concreteTenant'), 'post' => $keyParam('concretePost')]),
        );

        // tenantPost: Tenant (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPost/defaultTenant/concretePost',
            $url->route('tenantPost', [$keyParam('concretePost')]),
        );

        // tenantPost: Tenant (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPost/defaultTenant/concretePost',
            $url->route('tenantPost', ['post' => $keyParam('concretePost')]),
        );

        /**
         * One parameter with a default value, one without a default value.
         *
         * Binding field for the first {tenant} parameter with a default value.
         */
        $route = new Route(['GET'], 'tenantSlugPost/{tenant:slug}/{post}', ['as' => 'tenantSlugPost', fn () => '']);
        $routes->add($route);

        // tenantSlugPost: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/concreteTenantSlug/concretePost',
            $url->route('tenantSlugPost', [$slugParam('concreteTenantSlug'), $keyParam('concretePost')]),
        );

        // tenantSlugPost: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/concreteTenantSlug/concretePost',
            $url->route('tenantSlugPost', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost')]),
        );

        // tenantSlugPost: Tenant (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/defaultTenantSlug/concretePost',
            $url->route('tenantSlugPost', [$keyParam('concretePost')]),
        );

        // tenantSlugPost: Tenant (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/defaultTenantSlug/concretePost',
            $url->route('tenantSlugPost', ['post' => $keyParam('concretePost')]),
        );

        // Repeat the two assertions above without the 'tenant' default (without slug)
        $url->defaults(['tenant' => null]);

        // tenantSlugPost: Tenant (with default) omitted, post passed positionally, with the default value for 'tenant' (without slug) removed
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/defaultTenantSlug/concretePost',
            $url->route('tenantSlugPost', [$keyParam('concretePost')]),
        );

        // tenantSlugPost: Tenant (with default) omitted, post passed using key, with the default value for 'tenant' (without slug) removed
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/defaultTenantSlug/concretePost',
            $url->route('tenantSlugPost', ['post' => $keyParam('concretePost')]),
        );

        // Revert the default value for the tenant parameter back
        $url->defaults(['tenant' => 'defaultTenant']);

        /**
         * One parameter with a default value, one without a default value.
         *
         * Binding field for the second parameter without a default value.
         *
         * This is the only route in this test where we use a binding field
         * for a parameter that does not have a default value and is not
         * the first parameter. This is the simplest scenario so it doesn't
         * need to be tested as repetitively as the other scenarios which are
         * all special in some way.
         */
        $route = new Route(['GET'], 'tenantPostSlug/{tenant}/{post:slug}', ['as' => 'tenantPostSlug', fn () => '']);
        $routes->add($route);

        // tenantPostSlug: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostSlug/concreteTenant/concretePostSlug',
            $url->route('tenantPostSlug', [$keyParam('concreteTenant'), $slugParam('concretePostSlug')]),
        );

        // tenantPostSlug: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantPostSlug/concreteTenant/concretePostSlug',
            $url->route('tenantPostSlug', ['tenant' => $keyParam('concreteTenant'), 'post' => $slugParam('concretePostSlug')]),
        );

        // tenantPostSlug: Tenant (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostSlug/defaultTenant/concretePostSlug',
            $url->route('tenantPostSlug', [$slugParam('concretePostSlug')]),
        );

        // tenantPostSlug: Tenant (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostSlug/defaultTenant/concretePostSlug',
            $url->route('tenantPostSlug', ['post' => $slugParam('concretePostSlug')]),
        );

        /**
         * Two parameters with a default value, one without.
         *
         * Having established that passing parameters by key works fine above,
         * we mainly test positional parameters in variations of this route.
         */
        $route = new Route(['GET'], 'tenantTeamPost/{tenant}/{team}/{post}', ['as' => 'tenantTeamPost', fn () => '']);
        $routes->add($route);

        // tenantTeamPost: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamPost/concreteTenant/concreteTeam/concretePost',
            $url->route('tenantTeamPost', [$keyParam('concreteTenant'), $keyParam('concreteTeam'), $keyParam('concretePost')]),
        );

        // tenantTeamPost: Tenant (with default) omitted, team and post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamPost/defaultTenant/concreteTeam/concretePost',
            $url->route('tenantTeamPost', [$keyParam('concreteTeam'), $keyParam('concretePost')]),
        );

        // tenantTeamPost: Tenant and team (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamPost/defaultTenant/defaultTeam/concretePost',
            $url->route('tenantTeamPost', [$keyParam('concretePost')]),
        );

        // tenantTeamPost: Tenant passed by key, team (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamPost/concreteTenant/defaultTeam/concretePost',
            $url->route('tenantTeamPost', ['tenant' => $keyParam('concreteTenant'), $keyParam('concretePost')]),
        );

        /**
         * Two parameters with a default value, one without.
         *
         * The first {tenant} parameter also has a binding field.
         */
        $route = new Route(['GET'], 'tenantSlugTeamPost/{tenant:slug}/{team}/{post}', ['as' => 'tenantSlugTeamPost', fn () => '']);
        $routes->add($route);

        // tenantSlugTeamPost: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamPost/concreteTenantSlug/concreteTeam/concretePost',
            $url->route('tenantSlugTeamPost', [$slugParam('concreteTenantSlug'), $keyParam('concreteTeam'), $keyParam('concretePost')]),
        );

        // tenantSlugTeamPost: Tenant (with default) omitted, team and post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamPost/defaultTenantSlug/concreteTeam/concretePost',
            $url->route('tenantSlugTeamPost', [$keyParam('concreteTeam'), $keyParam('concretePost')]),
        );

        // tenantSlugTeamPost: Tenant and team (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamPost/defaultTenantSlug/defaultTeam/concretePost',
            $url->route('tenantSlugTeamPost', [$keyParam('concretePost')]),
        );

        // tenantSlugTeamPost: Tenant passed by key, team (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamPost/concreteTenantSlug/defaultTeam/concretePost',
            $url->route('tenantSlugTeamPost', ['tenant' => $slugParam('concreteTenantSlug'), $keyParam('concretePost')]),
        );

        /**
         * Two parameters with a default value, one without.
         *
         * The second {team} parameter also has a binding field.
         */
        $route = new Route(['GET'], 'tenantTeamSlugPost/{tenant}/{team:slug}/{post}', ['as' => 'tenantTeamSlugPost', fn () => '']);
        $routes->add($route);

        // tenantTeamSlugPost: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamSlugPost/concreteTenant/concreteTeamSlug/concretePost',
            $url->route('tenantTeamSlugPost', [$keyParam('concreteTenant'), $slugParam('concreteTeamSlug'), $keyParam('concretePost')]),
        );

        // tenantTeamSlugPost: Tenant (with default) omitted, team and post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamSlugPost/defaultTenant/concreteTeamSlug/concretePost',
            $url->route('tenantTeamSlugPost', [$slugParam('concreteTeamSlug'), $keyParam('concretePost')]),
        );

        // tenantTeamSlugPost: Tenant and team (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamSlugPost/defaultTenant/defaultTeamSlug/concretePost',
            $url->route('tenantTeamSlugPost', [$keyParam('concretePost')]),
        );

        // tenantTeamSlugPost: Tenant passed by key, team (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantTeamSlugPost/concreteTenantSlug/defaultTeamSlug/concretePost',
            $url->route('tenantTeamSlugPost', ['tenant' => $keyParam('concreteTenantSlug'), $keyParam('concretePost')]),
        );

        /**
         * Two parameters with a default value, one without.
         *
         * Both parameters with default values, {tenant} and {team}, also have binding fields.
         */
        $route = new Route(['GET'], 'tenantSlugTeamSlugPost/{tenant:slug}/{team:slug}/{post}', ['as' => 'tenantSlugTeamSlugPost', fn () => '']);
        $routes->add($route);

        // tenantSlugTeamSlugPost: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamSlugPost/concreteTenantSlug/concreteTeamSlug/concretePost',
            $url->route('tenantSlugTeamSlugPost', [$slugParam('concreteTenantSlug'), $slugParam('concreteTeamSlug'), $keyParam('concretePost')]),
        );

        // tenantSlugTeamSlugPost: Tenant (with default) omitted, team and post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamSlugPost/defaultTenantSlug/concreteTeamSlug/concretePost',
            $url->route('tenantSlugTeamSlugPost', [$slugParam('concreteTeamSlug'), $keyParam('concretePost')]),
        );

        // tenantSlugTeamSlugPost: Tenant and team (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamSlugPost/defaultTenantSlug/defaultTeamSlug/concretePost',
            $url->route('tenantSlugTeamSlugPost', [$keyParam('concretePost')]),
        );

        // tenantSlugTeamSlugPost: Tenant passed by key, team (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugTeamSlugPost/concreteTenantSlug/defaultTeamSlug/concretePost',
            $url->route('tenantSlugTeamSlugPost', ['tenant' => $slugParam('concreteTenantSlug'), $keyParam('concretePost')]),
        );

        /**
         * One parameter without a default value, one with a default value.
         *
         * Importantly, the parameter with the default value comes second.
         */
        $route = new Route(['GET'], 'postUser/{post}/{user}', ['as' => 'postUser', fn () => '']);
        $routes->add($route);

        // postUser: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/postUser/concretePost/concreteUser',
            $url->route('postUser', [$keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // postUser: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/postUser/concretePost/concreteUser',
            // Reversed order just to check it doesn't matter with named parameters
            $url->route('postUser', ['user' => $keyParam('concreteUser'), 'post' => $keyParam('concretePost')]),
        );

        // postUser: User (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/postUser/concretePost/defaultUser',
            $url->route('postUser', [$keyParam('concretePost')]),
        );

        // postUser: User (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/postUser/concretePost/defaultUser',
            $url->route('postUser', ['post' => $keyParam('concretePost')]),
        );

        /**
         * One parameter without a default value, one with a default value.
         *
         * Importantly, the parameter with the default value comes second.
         *
         * In this variation the first parameter, without a default value,
         * also has a binding field.
         */
        $route = new Route(['GET'], 'postSlugUser/{post:slug}/{user}', ['as' => 'postSlugUser', fn () => '']);
        $routes->add($route);

        // postSlugUser: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/postSlugUser/concretePostSlug/concreteUser',
            $url->route('postSlugUser', [$slugParam('concretePostSlug'), $keyParam('concreteUser')]),
        );

        // postSlugUser: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/postSlugUser/concretePostSlug/concreteUser',
            $url->route('postSlugUser', ['post' => $slugParam('concretePostSlug'), 'user' => $keyParam('concreteUser')]),
        );

        // postSlugUser: User (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/postSlugUser/concretePostSlug/defaultUser',
            $url->route('postSlugUser', [$slugParam('concretePostSlug')]),
        );

        // postSlugUser: User (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/postSlugUser/concretePostSlug/defaultUser',
            $url->route('postSlugUser', ['post' => $slugParam('concretePostSlug')]),
        );

        /**
         * One parameter without a default value, one with a default value.
         *
         * Importantly, the parameter with the default value comes second.
         *
         * In this variation the second parameter, with a default value,
         * also has a binding field.
         */
        $route = new Route(['GET'], 'postUserSlug/{post}/{user:slug}', ['as' => 'postUserSlug', fn () => '']);
        $routes->add($route);

        // postUserSlug: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/postUserSlug/concretePost/concreteUserSlug',
            $url->route('postUserSlug', [$keyParam('concretePost'), $slugParam('concreteUserSlug')]),
        );

        // postUserSlug: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/postUserSlug/concretePost/concreteUserSlug',
            $url->route('postUserSlug', ['post' => $keyParam('concretePost'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // postUserSlug: User (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/postUserSlug/concretePost/defaultUserSlug',
            $url->route('postUserSlug', [$keyParam('concretePost')]),
        );

        // postUserSlug: User (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/postUserSlug/concretePost/defaultUserSlug',
            $url->route('postUserSlug', ['post' => $keyParam('concretePost')]),
        );

        /**
         * One parameter without a default value, one with a default value.
         *
         * Importantly, the parameter with the default value comes second.
         *
         * In this variation, both parameters have binding fields.
         */
        $route = new Route(['GET'], 'postSlugUserSlug/{post:slug}/{user:slug}', ['as' => 'postSlugUserSlug', fn () => '']);
        $routes->add($route);

        // postSlugUserSlug: Both parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/postSlugUserSlug/concretePostSlug/concreteUserSlug',
            $url->route('postSlugUserSlug', [$slugParam('concretePostSlug'), $slugParam('concreteUserSlug')]),
        );

        // postSlugUserSlug: Both parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/postSlugUserSlug/concretePostSlug/concreteUserSlug',
            $url->route('postSlugUserSlug', ['post' => $slugParam('concretePostSlug'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // postSlugUserSlug: User (with default) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/postSlugUserSlug/concretePostSlug/defaultUserSlug',
            $url->route('postSlugUserSlug', [$slugParam('concretePostSlug')]),
        );

        // postSlugUserSlug: User (with default) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/postSlugUserSlug/concretePostSlug/defaultUserSlug',
            $url->route('postSlugUserSlug', ['post' => $slugParam('concretePostSlug')]),
        );

        /**
         * Parameter without a default value in between two parameters with default values.
         */
        $route = new Route(['GET'], 'tenantPostUser/{tenant}/{post}/{user}', ['as' => 'tenantPostUser', fn () => '']);
        $routes->add($route);

        // tenantPostUser: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/concreteTenant/concretePost/concreteUser',
            $url->route('tenantPostUser', [$keyParam('concreteTenant'), $keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantPostUser: Tenant parameter omitted, post and user passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/concreteUser',
            $url->route('tenantPostUser', [$keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantPostUser: Both tenant and user (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/defaultUser',
            $url->route('tenantPostUser', [$keyParam('concretePost')]),
        );

        // tenantPostUser: All parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/concreteTenant/concretePost/concreteUser',
            $url->route('tenantPostUser', ['tenant' => $keyParam('concreteTenant'), 'post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantPostUser: Both tenant and user (with defaults) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/defaultUser',
            $url->route('tenantPostUser', ['post' => $keyParam('concretePost')]),
        );

        // tenantPostUser: Tenant parameter (with default) omitted, post and user passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/concreteUser',
            $url->route('tenantPostUser', ['post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantPostUser: User parameter (with default) omitted, tenant and post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/concreteTenant/concretePost/defaultUser',
            $url->route('tenantPostUser', ['tenant' => $keyParam('concreteTenant'), 'post' => $keyParam('concretePost')]),
        );

        /**
         * Parameter without a default value in between two parameters with a default value.
         *
         * In this variation of this route, the first {tenant} parameter, with a default value,
         * also has a binding field.
         */
        $route = new Route(['GET'], 'tenantSlugPostUser/{tenant:slug}/{post}/{user}', ['as' => 'tenantSlugPostUser', fn () => '']);
        $routes->add($route);

        // tenantSlugPostUser: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', [$slugParam('concreteTenantSlug'), $keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Tenant parameter omitted, post and user passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', [$keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Both tenant and user (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', [$keyParam('concretePost')]),
        );

        // tenantSlugPostUser: All parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Both tenant and user (with defaults) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', ['post' => $keyParam('concretePost')]),
        );

        // tenantSlugPostUser: Tenant parameter (with default) omitted, post and user passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', ['post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: User parameter (with default) omitted, tenant and post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost')]),
        );

        /**
         * Parameter without a default value in between two parameters with a default value.
         *
         * In this variation of this route, the last {user} parameter, with a default value,
         * also has a binding field.
         */
        $route = new Route(['GET'], 'tenantPostUserSlug/{tenant}/{post}/{user:slug}', ['as' => 'tenantPostUserSlug', fn () => '']);
        $routes->add($route);

        // tenantPostUserSlug: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/concreteTenant/concretePost/concreteUserSlug',
            $url->route('tenantPostUserSlug', [$keyParam('concreteTenant'), $keyParam('concretePost'), $slugParam('concreteUserSlug')]),
        );

        // tenantPostUserSlug: Tenant parameter omitted, post and user passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/defaultTenant/concretePost/concreteUserSlug',
            $url->route('tenantPostUserSlug', [$keyParam('concretePost'), $slugParam('concreteUserSlug')]),
        );

        // tenantPostUserSlug: Both tenant and user (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/defaultTenant/concretePost/defaultUserSlug',
            $url->route('tenantPostUserSlug', [$keyParam('concretePost')]),
        );

        // tenantPostUserSlug: All parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/concreteTenant/concretePost/concreteUserSlug',
            $url->route('tenantPostUserSlug', ['tenant' => $keyParam('concreteTenant'), 'post' => $keyParam('concretePost'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // tenantPostUserSlug: Both tenant and user (with defaults) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/defaultTenant/concretePost/defaultUserSlug',
            $url->route('tenantPostUserSlug', ['post' => $keyParam('concretePost')]),
        );

        // tenantPostUserSlug: Tenant parameter (with default) omitted, post and user passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/defaultTenant/concretePost/concreteUserSlug',
            $url->route('tenantPostUserSlug', ['post' => $keyParam('concretePost'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // tenantPostUserSlug: User parameter (with default) omitted, tenant and post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantPostUserSlug/concreteTenant/concretePost/defaultUserSlug',
            $url->route('tenantPostUserSlug', ['tenant' => $keyParam('concreteTenant'), 'post' => $keyParam('concretePost')]),
        );

        /**
         * Parameter without a default value in between two parameters with a default value.
         *
         * In this variation of this route, the first {tenant} parameter, with a default value,
         * also has a binding field.
         */
        $route = new Route(['GET'], 'tenantSlugPostUser/{tenant:slug}/{post}/{user}', ['as' => 'tenantSlugPostUser', fn () => '']);
        $routes->add($route);

        // tenantSlugPostUser: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', [$slugParam('concreteTenantSlug'), $keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Tenant parameter omitted, post and user passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', [$keyParam('concretePost'), $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Both tenant and user (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', [$keyParam('concretePost')]),
        );

        // tenantSlugPostUser: All parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: Both tenant and user (with defaults) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', ['post' => $keyParam('concretePost')]),
        );

        // tenantSlugPostUser: Tenant parameter (with default) omitted, post and user passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/defaultTenantSlug/concretePost/concreteUser',
            $url->route('tenantSlugPostUser', ['post' => $keyParam('concretePost'), 'user' => $keyParam('concreteUser')]),
        );

        // tenantSlugPostUser: User parameter (with default) omitted, tenant and post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUser/concreteTenantSlug/concretePost/defaultUser',
            $url->route('tenantSlugPostUser', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost')]),
        );

        /**
         * Parameter without a default value in between two parameters with a default value.
         *
         * In this variation of this route, both fields with a default value, {tenant} and
         * {user}, also have binding fields.
         */
        $route = new Route(['GET'], 'tenantSlugPostUserSlug/{tenant:slug}/{post}/{user:slug}', ['as' => 'tenantSlugPostUserSlug', fn () => '']);
        $routes->add($route);

        // tenantSlugPostUserSlug: All parameters passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/concreteTenantSlug/concretePost/concreteUserSlug',
            $url->route('tenantSlugPostUserSlug', [$slugParam('concreteTenantSlug'), $keyParam('concretePost'), $slugParam('concreteUserSlug')]),
        );

        // tenantSlugPostUserSlug: Tenant parameter omitted, post and user passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/defaultTenantSlug/concretePost/concreteUserSlug',
            $url->route('tenantSlugPostUserSlug', [$keyParam('concretePost'), $slugParam('concreteUserSlug')]),
        );

        // tenantSlugPostUserSlug: Both tenant and user (with defaults) omitted, post passed positionally
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/defaultTenantSlug/concretePost/defaultUserSlug',
            $url->route('tenantSlugPostUserSlug', [$keyParam('concretePost')]),
        );

        // tenantSlugPostUserSlug: All parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/concreteTenantSlug/concretePost/concreteUserSlug',
            $url->route('tenantSlugPostUserSlug', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // tenantSlugPostUserSlug: Both tenant and user (with defaults) omitted, post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/defaultTenantSlug/concretePost/defaultUserSlug',
            $url->route('tenantSlugPostUserSlug', ['post' => $keyParam('concretePost')]),
        );

        // tenantSlugPostUserSlug: Tenant parameter (with default) omitted, post and user passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/defaultTenantSlug/concretePost/concreteUserSlug',
            $url->route('tenantSlugPostUserSlug', ['post' => $keyParam('concretePost'), 'user' => $slugParam('concreteUserSlug')]),
        );

        // tenantSlugPostUserSlug: User parameter (with default) omitted, tenant and post passed using key
        $this->assertSame(
            'https://www.foo.com/tenantSlugPostUserSlug/concreteTenantSlug/concretePost/defaultUserSlug',
            $url->route('tenantSlugPostUserSlug', ['tenant' => $slugParam('concreteTenantSlug'), 'post' => $keyParam('concretePost')]),
        );
    }

    public function testComplexRouteGenerationWithDefaultsAndMixedParameterSyntax()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $url->defaults([
            'tenant' => 'defaultTenant',
            'user' => 'defaultUser',
        ]);

        /**
         * Parameter without a default value in between two parameters with default values.
         */
        $route = new Route(['GET'], 'tenantPostUser/{tenant}/{post}/{user}', ['as' => 'tenantPostUser', fn () => '']);
        $routes->add($route);

        // If the required post parameter is specified using a key,
        // the positional parameter is used for the user parameter.
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/concreteUser',
            $url->route('tenantPostUser', ['post' => 'concretePost', 'concreteUser']),
        );

        /**
         * Two parameters without default values in between two parameters with default values.
         */
        $route = new Route(['GET'], 'tenantPostCommentUser/{tenant}/{post}/{comment}/{user}', ['as' => 'tenantPostCommentUser', fn () => '']);
        $routes->add($route);

        // Pass first required parameter using a key, second positionally
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/defaultUser',
            $url->route('tenantPostCommentUser', ['post' => 'concretePost', 'concreteComment']),
        );

        // Pass first required parameter positionally, second using a key
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/defaultUser',
            $url->route('tenantPostCommentUser', ['concretePost', 'comment' => 'concreteComment']),
        );

        // Verify that this is order-independent
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/defaultUser',
            $url->route('tenantPostCommentUser', ['comment' => 'concreteComment', 'concretePost']),
        );

        // Both required params passed with keys, positional parameter goes to the user param
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['post' => 'concretePost', 'comment' => 'concreteComment', 'concreteUser']),
        );

        // First required param passed with a key, remaining params go to the last two route params
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['post' => 'concretePost', 'concreteComment', 'concreteUser']),
        );

        // Comment parameter passed with a key, remaining params filled (last to last)
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['concretePost', 'comment' => 'concreteComment', 'concreteUser']),
        );

        // Verify that this is order-independent
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/defaultTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['comment' => 'concreteComment', 'concretePost', 'concreteUser']),
        );

        // Both default parameters passed positionally, required parameters passed with keys
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/concreteTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['concreteTenant', 'post' => 'concretePost', 'comment' => 'concreteComment', 'concreteUser']),
        );

        // Verify that the positional parameters may come anywhere in the array
        $this->assertSame(
            'https://www.foo.com/tenantPostCommentUser/concreteTenant/concretePost/concreteComment/concreteUser',
            $url->route('tenantPostCommentUser', ['post' => 'concretePost', 'comment' => 'concreteComment', 'concreteTenant', 'concreteUser']),
        );
    }

    public function testDefaultsCanBeCombinedWithExtraQueryParameters()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $url->defaults([
            'tenant' => 'defaultTenant',
            'tenant:slug' => 'defaultTenantSlug',
            'user' => 'defaultUser',
        ]);

        $slugParam = fn ($value) => tap(new RoutableInterfaceStub, fn ($routable) => $routable->slug = $value);

        /**
         * One parameter with a default value, one parameter without a default value.
         */
        $route = new Route(['GET'], 'tenantPost/{tenant}/{post}', ['as' => 'tenantPost', fn () => '']);
        $routes->add($route);

        // tenantPost: Extra positional parameters without values are interpreted as query strings
        $this->assertSame(
            'https://www.foo.com/tenantPost/concreteTenant/concretePost?extraQuery',
            $url->route('tenantPost', ['concreteTenant', 'concretePost', 'extraQuery']),
        );

        // tenantPost: Query parameters without values go at the end
        $this->assertSame(
            'https://www.foo.com/tenantPost/concreteTenant/concretePost?extra=query&extraQuery',
            $url->route('tenantPost', ['concreteTenant', 'concretePost', 'extraQuery', 'extra' => 'query']),
        );

        // tenantPost: Defaults can be used with *named* query parameters
        $this->assertSame(
            'https://www.foo.com/tenantPost/defaultTenant/concretePost?extra=query',
            $url->route('tenantPost', ['concretePost', 'extra' => 'query']),
        );

        // tenantPost: Named query parameters can be placed anywhere in the parameters array
        $this->assertSame(
            'https://www.foo.com/tenantPost/concreteTenant/concretePost?extra=query',
            $url->route('tenantPost', ['concreteTenant', 'extra' => 'query', 'concretePost']),
        );

        /**
         * One parameter with a default value, one parameter without a default value.
         *
         * The first parameter with a default value, {tenant}, also has a binding field.
         */
        $route = new Route(['GET'], 'tenantSlugPost/{tenant:slug}/{post}', ['as' => 'tenantSlugPost', fn () => '']);
        $routes->add($route);

        // tenantSlugPost: Extra positional parameters without values are interpreted as query strings
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/concreteTenantSlug/concretePost?extraQuery',
            $url->route('tenantSlugPost', [$slugParam('concreteTenantSlug'), 'concretePost', 'extraQuery']),
        );

        // tenantSlugPost: Query parameters without values go at the end
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/concreteTenantSlug/concretePost?extra=query&extraQuery',
            $url->route('tenantSlugPost', [$slugParam('concreteTenantSlug'), 'concretePost', 'extraQuery', 'extra' => 'query']),
        );

        // tenantSlugPost: Defaults can be used with *named* query parameters
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/defaultTenantSlug/concretePost?extra=query',
            $url->route('tenantSlugPost', ['concretePost', 'extra' => 'query']),
        );

        // tenantSlugPost: Named query parameters can be placed anywhere in the parameters array
        $this->assertSame(
            'https://www.foo.com/tenantSlugPost/concreteTenantSlug/concretePost?extra=query',
            $url->route('tenantSlugPost', [$slugParam('concreteTenantSlug'), 'extra' => 'query', 'concretePost']),
        );

        /**
         * Parameter without a default value in between two parameters with default values.
         */
        $route = new Route(['GET'], 'tenantPostUser/{tenant}/{post}/{user}', ['as' => 'tenantPostUser', fn () => '']);
        $routes->add($route);

        // tenantPostUser: Query string parameters may be passed positionally if
        // all route parameters are passed as well, i.e. defaults are not used.
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/concreteTenant/concretePost/concreteUser?extraQuery',
            $url->route('tenantPostUser', ['concreteTenant', 'concretePost', 'concreteUser', 'extraQuery']),
        );

        // tenantPostUser: Query string parameters can be passed as key-value
        // pairs if all route params are passed as well, i.e. no defaults.
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/concreteTenant/concretePost/concreteUser?extraQuery',
            $url->route('tenantPostUser', ['concreteTenant', 'concretePost', 'concreteUser', 'extraQuery']),
        );

        // tenantPostUser: With omitted default parameters, query string parameters
        // can only be specified using key-value pairs. Positional query string
        // parameters would be interpreted as route parameters instead.
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/concreteUser?extra=query',
            $url->route('tenantPostUser', ['concretePost', 'concreteUser', 'extra' => 'query']),
        );

        // tenantPostUser: Use defaults for tenant and user, pass post positionally
        // and add an extra query string parameter as a key-value pair.
        $this->assertSame(
            'https://www.foo.com/tenantPostUser/defaultTenant/concretePost/defaultUser?extra=query',
            $url->route('tenantPostUser', ['concretePost', 'extra' => 'query']),
        );
    }

    public function testUrlGenerationWithOptionalParameters(): void
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('https://www.foo.com/')
        );

        $url->defaults([
            'tenant' => 'defaultTenant',
            'user' => 'defaultUser',
        ]);

        /**
         * Route with one required parameter and one optional parameter.
         */
        $route = new Route(['GET'], 'postOptionalMethod/{post}/{method?}', ['as' => 'postOptionalMethod', fn () => '']);
        $routes->add($route);

        $this->assertSame(
            'https://www.foo.com/postOptionalMethod/1',
            $url->route('postOptionalMethod', 1),
        );

        $this->assertSame(
            'https://www.foo.com/postOptionalMethod/1/2',
            $url->route('postOptionalMethod', [1, 2]),
        );

        /**
         * Route with two optional parameters.
         */
        $route = new Route(['GET'], 'optionalPostOptionalMethod/{post}/{method?}', ['as' => 'optionalPostOptionalMethod', fn () => '']);
        $routes->add($route);

        $this->assertSame(
            'https://www.foo.com/optionalPostOptionalMethod/1',
            $url->route('optionalPostOptionalMethod', 1),
        );

        $this->assertSame(
            'https://www.foo.com/optionalPostOptionalMethod/1/2',
            $url->route('optionalPostOptionalMethod', [1, 2]),
        );

        /**
         * Route with one default parameter, one required parameter, and one optional parameter.
         */
        $route = new Route(['GET'], 'tenantPostOptionalMethod/{tenant}/{post}/{method?}', ['as' => 'tenantPostOptionalMethod', fn () => '']);
        $routes->add($route);

        // Passing one parameter
        $this->assertSame(
            'https://www.foo.com/tenantPostOptionalMethod/defaultTenant/concretePost',
            $url->route('tenantPostOptionalMethod', ['concretePost']),
        );

        // Passing two parameters: optional parameter is prioritized over parameter with a default value
        $this->assertSame(
            'https://www.foo.com/tenantPostOptionalMethod/defaultTenant/concretePost/concreteMethod',
            $url->route('tenantPostOptionalMethod', ['concretePost', 'concreteMethod']),
        );

        // Passing all three parameters
        $this->assertSame(
            'https://www.foo.com/tenantPostOptionalMethod/concreteTenant/concretePost/concreteMethod',
            $url->route('tenantPostOptionalMethod', ['concreteTenant', 'concretePost', 'concreteMethod']),
        );

        /**
         * Route with two default parameters, one required parameter, and one optional parameter.
         */
        $route = new Route(['GET'], 'tenantUserPostOptionalMethod/{tenant}/{user}/{post}/{method?}', ['as' => 'tenantUserPostOptionalMethod', fn () => '']);
        $routes->add($route);

        // Passing one parameter
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/defaultUser/concretePost',
            $url->route('tenantUserPostOptionalMethod', ['concretePost']),
        );

        // Passing two parameters: optional parameter is prioritized over parameters with default values
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/defaultUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['concretePost', 'concreteMethod']),
        );

        // Passing three parameters: only the leftmost parameter with a default value uses its default value
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/concreteUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['concreteUser', 'concretePost', 'concreteMethod']),
        );

        // Same as the assertion above, but using some named parameters
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/concreteUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['user' => 'concreteUser', 'concretePost', 'concreteMethod']),
        );

        // Also using a named parameter, but this time for the post parameter
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/concreteUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['concreteUser', 'post' => 'concretePost', 'concreteMethod']),
        );

        // Also using a named parameter, but this time for the optional method parameter
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/defaultTenant/concreteUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['concreteUser', 'concretePost', 'method' => 'concreteMethod']),
        );

        // Passing all four parameters
        $this->assertSame(
            'https://www.foo.com/tenantUserPostOptionalMethod/concreteTenant/concreteUser/concretePost/concreteMethod',
            $url->route('tenantUserPostOptionalMethod', ['concreteTenant', 'concreteUser', 'concretePost', 'concreteMethod']),
        );

        /**
         * Route with a default parameter, a required parameter, another default parameter, and finally an optional parameter.
         *
         * This tests interleaved default parameters when combined with optional parameters.
         */
        $route = new Route(['GET'], 'tenantPostUserOptionalMethod/{tenant}/{post}/{user}/{method?}', ['as' => 'tenantPostUserOptionalMethod', fn () => '']);
        $routes->add($route);

        // Passing one parameter
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/defaultTenant/concretePost/defaultUser',
            $url->route('tenantPostUserOptionalMethod', ['concretePost']),
        );

        // Passing two parameters: optional parameter is prioritized over parameters with default values
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/defaultTenant/concretePost/defaultUser/concreteMethod',
            $url->route('tenantPostUserOptionalMethod', ['concretePost', 'concreteMethod']),
        );

        // Same as the assertion above, but using some named parameters
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/defaultTenant/concretePost/defaultUser/concreteMethod',
            $url->route('tenantPostUserOptionalMethod', ['post' => 'concretePost', 'concreteMethod']),
        );

        // Also using a named parameter, but this time for the optional parameter
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/defaultTenant/concretePost/defaultUser/concreteMethod',
            $url->route('tenantPostUserOptionalMethod', ['concretePost', 'method' => 'concreteMethod']),
        );

        // Passing three parameters: only the leftmost parameter with a default value uses its default value
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/defaultTenant/concretePost/concreteUser/concreteMethod',
            $url->route('tenantPostUserOptionalMethod', ['concretePost', 'concreteUser', 'concreteMethod']),
        );

        // Passing all four parameters
        $this->assertSame(
            'https://www.foo.com/tenantPostUserOptionalMethod/concreteTenant/concretePost/concreteUser/concreteMethod',
            $url->route('tenantPostUserOptionalMethod', ['concreteTenant', 'concretePost', 'concreteUser', 'concreteMethod']),
        );
    }
}

class RoutableInterfaceStub implements UrlRoutable
{
    public $key;
    public $slug = 'test-slug';

    public function getRouteKey()
    {
        return $this->{$this->getRouteKeyName()};
    }

    public function getRouteKeyName()
    {
        return 'key';
    }

    public function resolveRouteBinding($routeKey, $field = null)
    {
        //
    }

    public function resolveChildRouteBinding($childType, $routeKey, $field = null)
    {
        //
    }
}

class InvokableActionStub
{
    public function __invoke()
    {
        return 'hello';
    }
}

class RoutingUrlGeneratorTestUser extends Model
{
    protected $fillable = ['uuid'];
}
