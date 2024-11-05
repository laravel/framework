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

        /*
         * Test HTTPS request URL generation...
         */
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
         * Optional parameter
         */
        $route = new Route(['GET'], 'foo/bar/{baz?}', ['as' => 'optional']);
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
            //
        }]);
        $routes->add($route);

        /*
         * With backed enum name and domain
         */
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

        /*
         * Named Routes
         */
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

        /*
         * Controller Route Route
         */
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

    /**
     * @todo Fix bug related to route keys
     *
     * @link https://github.com/laravel/framework/pull/42425
     */
    public function testRoutableInterfaceRoutingWithSeparateBindingFieldOnlyForSecondParameter()
    {
        $this->markTestSkipped('See https://github.com/laravel/framework/pull/43255');

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

        /*
         * Named Routes
         */
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

        /*
         * Named Routes
         */
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

        /*
         * Wildcards & Domains...
         */
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

        /*
         * Wildcards & Domains...
         */
        $route = new Route(['GET'], 'foo/bar/{baz}', ['as' => 'bar', 'domain' => 'sub.{foo}.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com:8080/foo/bar', $url->route('foo'));
        $this->assertSame('http://sub.taylor.com:8080/foo/bar/otwell', $url->route('bar', ['taylor', 'otwell']));
    }

    public function testRoutesWithDomainsStripsProtocols()
    {
        /*
         * http:// Route
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $route = new Route(['GET'], 'foo/bar', ['as' => 'foo', 'domain' => 'http://sub.foo.com']);
        $routes->add($route);

        $this->assertSame('http://sub.foo.com/foo/bar', $url->route('foo'));

        /*
         * https:// Route
         */
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

        /*
         * When on HTTPS, no need to specify 443
         */
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

    public function testForceRootUrl()
    {
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->forceRootUrl('https://www.bar.com');
        $this->assertSame('http://www.bar.com/foo/bar', $url->to('foo/bar'));

        // Ensure trailing slash is trimmed from root URL as UrlGenerator already handles this
        $url->forceRootUrl('http://www.foo.com/');
        $this->assertSame('http://www.foo.com/bar', $url->to('/bar'));

        /*
         * Route Based...
         */
        $url = new UrlGenerator(
            $routes = new RouteCollection,
            Request::create('http://www.foo.com/')
        );

        $url->forceScheme('https');
        $route = new Route(['GET'], '/foo', ['as' => 'plain']);
        $routes->add($route);

        $this->assertSame('https://www.foo.com/foo', $url->route('plain'));

        $url->forceRootUrl('https://www.bar.com');
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
