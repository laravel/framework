<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class RoutingRouteTest extends PHPUnit_Framework_TestCase {

	public function testBasicDispatchingOfRoutes()
	{
		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$route = $router->get('foo/bar', array('domain' => 'api.{name}.bar', function($name) { return $name; }));
		$route = $router->get('foo/bar', array('domain' => 'api.{name}.baz', function($name) { return $name; }));
		$this->assertEquals('taylor', $router->dispatch(Request::create('http://api.taylor.bar/foo/bar', 'GET'))->getContent());
		$this->assertEquals('dayle', $router->dispatch(Request::create('http://api.dayle.baz/foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->post('foo/bar', function() { return 'post hello'; });
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
		$this->assertEquals('post hello', $router->dispatch(Request::create('foo/bar', 'POST'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/{bar}', function($name) { return $name; });
		$this->assertEquals('taylor', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/{bar}/{baz?}', function($name, $age = 25) { return $name.$age; });
		$this->assertEquals('taylor25', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/{name}/boom/{age?}/{location?}', function($name, $age = 25, $location = 'AR') { return $name.$age.$location; });
		$this->assertEquals('taylor30AR', $router->dispatch(Request::create('foo/taylor/boom/30', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('{bar}/{baz?}', function($name, $age = 25) { return $name.$age; });
		$this->assertEquals('taylor25', $router->dispatch(Request::create('taylor', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('{baz?}', function($age = 25) { return $age; });
		$this->assertEquals('25', $router->dispatch(Request::create('/', 'GET'))->getContent());
		$this->assertEquals('30', $router->dispatch(Request::create('30', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('{foo?}/{baz?}', array('as' => 'foo', function($name = 'taylor', $age = 25) { return $name.$age; }));
		$this->assertEquals('taylor25', $router->dispatch(Request::create('/', 'GET'))->getContent());
		$this->assertEquals('fred25', $router->dispatch(Request::create('fred', 'GET'))->getContent());
		$this->assertEquals('fred30', $router->dispatch(Request::create('fred/30', 'GET'))->getContent());
		$this->assertTrue($router->currentRouteNamed('foo'));

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$this->assertEquals('', $router->dispatch(Request::create('foo/bar', 'HEAD'))->getContent());

		$router = $this->getRouter();
		$router->any('foo/bar', function() { return 'hello'; });
		$this->assertEquals('', $router->dispatch(Request::create('foo/bar', 'HEAD'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'first'; });
		$router->get('foo/bar', function() { return 'second'; });
		$this->assertEquals('second', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
	}


        public function testNonGreedyMatches()
        {
            $route = new Route('GET', 'images/{id}.{ext}', function() {});

            $request1 = Request::create('images/1.png', 'GET');
            $this->assertTrue($route->matches($request1));
            $route->bind($request1);
            $this->assertEquals('1', $route->parameter('id'));
            $this->assertEquals('png', $route->parameter('ext'));

            $request2 = Request::create('images/12.png', 'GET');
            $this->assertTrue($route->matches($request2));
            $route->bind($request2);
            $this->assertEquals('12', $route->parameter('id'));
            $this->assertEquals('png', $route->parameter('ext'));
        }


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testRoutesDontMatchNonMatchingPathsWithLeadingOptionals()
	{
		$router = $this->getRouter();
		$router->get('{baz?}', function($age = 25) { return $age; });
		$this->assertEquals('25', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testRoutesDontMatchNonMatchingDomain()
	{
		$router = $this->getRouter();
		$route = $router->get('foo/bar', array('domain' => 'api.foo.bar', function() { return 'hello'; }));
		$this->assertEquals('hello', $router->dispatch(Request::create('http://api.baz.boom/foo/bar', 'GET'))->getContent());
	}


	public function testDispatchingOfControllers()
	{
		$router = $this->getRouter();
		$router->get('foo', 'RouteTestControllerDispatchStub@foo');
		$this->assertEquals('bar', $router->dispatch(Request::create('foo', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->filter('foo', function()
		{
			return 'filter';
		});
		$router->get('bar', 'RouteTestControllerDispatchStub@bar');
		$this->assertEquals('filter', $router->dispatch(Request::create('bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('baz', 'RouteTestControllerDispatchStub@baz');
		$this->assertEquals('filtered', $router->dispatch(Request::create('baz', 'GET'))->getContent());


		/**
		 * Test filters disabled...
		 */
		$router = $this->getRouter();
		$router->filter('foo', function()
		{
			return 'filter';
		});
		$router->disableFilters();
		$router->get('bar', 'RouteTestControllerDispatchStub@bar');
		$this->assertEquals('baz', $router->dispatch(Request::create('bar', 'GET'))->getContent());

		$this->assertTrue($router->currentRouteUses('RouteTestControllerDispatchStub@bar'));
	}


	public function testBasicBeforeFilters()
	{
		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->before(function() { return 'foo!'; });
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->before('RouteTestFilterStub');
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->before('RouteTestFilterStub@handle');
		$this->assertEquals('handling!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo', function() { return 'hello'; }));
		$router->filter('foo', function() { return 'foo!'; });
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo:25', function() { return 'hello'; }));
		$router->filter('foo', function($route, $request, $age) { return $age; });
		$this->assertEquals('25', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo:bar,baz', function() { return 'hello'; }));
		$router->filter('foo', function($route, $request, $bar, $baz) { return $bar.$baz; });
		$this->assertEquals('barbaz', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo:bar,baz|bar:boom', function() { return 'hello'; }));
		$router->filter('foo', function($route, $request, $bar, $baz) { return null; });
		$router->filter('bar', function($route, $request, $boom) { return $boom; });
		$this->assertEquals('boom', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		/**
		 * Basic filter parameter
		 */
		unset($_SERVER['__route.filter']);
		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo:bar', function() { return 'hello'; }));
		$router->filter('foo', function($route, $request, $value = null) { $_SERVER['__route.filter'] = $value; });
		$router->dispatch(Request::create('foo/bar', 'GET'));
		$this->assertEquals('bar', $_SERVER['__route.filter']);

		/**
		 * Optional filter parameter
		 */
		unset($_SERVER['__route.filter']);
		$router = $this->getRouter();
		$router->get('foo/bar', array('before' => 'foo', function() { return 'hello'; }));
		$router->filter('foo', function($route, $request, $value = null) { $_SERVER['__route.filter'] = $value; });
		$router->dispatch(Request::create('foo/bar', 'GET'));
		$this->assertEquals(null, $_SERVER['__route.filter']);
	}


	public function testFiltersCanBeDisabled()
	{
		$router = $this->getRouter();
		$router->disableFilters();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->before(function() { return 'foo!'; });
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->disableFilters();
		$router->get('foo/bar', array('before' => 'foo', function() { return 'hello'; }));
		$router->filter('foo', function() { return 'foo!'; });
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
	}


	public function testGlobalAfterFilters()
	{
		unset($_SERVER['__filter.after']);
		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->after(function() { $_SERVER['__filter.after'] = true; return 'foo!'; });

		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
		$this->assertTrue($_SERVER['__filter.after']);
	}


	public function testBasicAfterFilters()
	{
		unset($_SERVER['__filter.after']);
		$router = $this->getRouter();
		$router->get('foo/bar', array('after' => 'foo', function() { return 'hello'; }));
		$router->filter('foo', function() { $_SERVER['__filter.after'] = true; return 'foo!'; });

		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
		$this->assertTrue($_SERVER['__filter.after']);
	}


	public function testPatternBasedFilters()
	{
		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->filter('foo', function($route, $request, $bar) { return 'foo'.$bar; });
		$router->when('foo/*', 'foo:bar');
		$this->assertEquals('foobar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->filter('foo', function($route, $request, $bar) { return 'foo'.$bar; });
		$router->when('bar/*', 'foo:bar');
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->filter('foo', function($route, $request, $bar) { return 'foo'.$bar; });
		$router->when('foo/*', 'foo:bar', array('post'));
		$this->assertEquals('hello', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->filter('foo', function($route, $request, $bar) { return 'foo'.$bar; });
		$router->when('foo/*', 'foo:bar', array('get'));
		$this->assertEquals('foobar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());

		$router = $this->getRouter();
		$router->get('foo/bar', function() { return 'hello'; });
		$router->filter('foo', function($route, $request) {});
		$router->filter('bar', function($route, $request) { return 'bar'; });
		$router->when('foo/*', 'foo|bar', array('get'));
		$this->assertEquals('bar', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
	}


	public function testMatchesMethodAgainstRequests()
	{
		/**
		 * Basic
		 */
		$request = Request::create('foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', function() {});
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/bar', 'GET');
		$route = new Route('GET', 'foo', function() {});
		$this->assertFalse($route->matches($request));

		/**
		 * Method checks
		 */
		$request = Request::create('foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', function() {});
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/bar', 'POST');
		$route = new Route('GET', 'foo', function() {});
		$this->assertFalse($route->matches($request));

		/**
		 * Domain checks
		 */
		$request = Request::create('http://something.foo.com/foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', array('domain' => '{foo}.foo.com', function() {}));
		$this->assertTrue($route->matches($request));

		$request = Request::create('http://something.bar.com/foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', array('domain' => '{foo}.foo.com', function() {}));
		$this->assertFalse($route->matches($request));

		/**
		 * HTTPS checks
		 */
		$request = Request::create('https://foo.com/foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', array('https', function() {}));
		$this->assertTrue($route->matches($request));

		$request = Request::create('http://foo.com/foo/bar', 'GET');
		$route = new Route('GET', 'foo/{bar}', array('https', function() {}));
		$this->assertFalse($route->matches($request));
	}


	public function testWherePatternsProperlyFilter()
	{
		$request = Request::create('foo/123', 'GET');
		$route = new Route('GET', 'foo/{bar}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/123abc', 'GET');
		$route = new Route('GET', 'foo/{bar}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertFalse($route->matches($request));

		/**
		 * Optional
		 */
		$request = Request::create('foo/123', 'GET');
		$route = new Route('GET', 'foo/{bar?}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/123', 'GET');
		$route = new Route('GET', 'foo/{bar?}/{baz?}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/123/foo', 'GET');
		$route = new Route('GET', 'foo/{bar?}/{baz?}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertTrue($route->matches($request));

		$request = Request::create('foo/123abc', 'GET');
		$route = new Route('GET', 'foo/{bar?}', function() {});
		$route->where('bar', '[0-9]+');
		$this->assertFalse($route->matches($request));
	}


	public function testDotDoesNotMatchEverything()
	{
		$route = new Route('GET', 'images/{id}.{ext}', function() {});

		$request1 = Request::create('images/1.png', 'GET');
		$this->assertTrue($route->matches($request1));
		$route->bind($request1);
		$this->assertEquals('1', $route->parameter('id'));
		$this->assertEquals('png', $route->parameter('ext'));

		$request2 = Request::create('images/12.png', 'GET');
		$this->assertTrue($route->matches($request2));
		$route->bind($request2);
		$this->assertEquals('12', $route->parameter('id'));
		$this->assertEquals('png', $route->parameter('ext'));

	}


	public function testRouteBinding()
	{
		$router = $this->getRouter();
		$router->get('foo/{bar}', function($name) { return $name; });
		$router->bind('bar', function($value) { return strtoupper($value); });
		$this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
	}


	public function testModelBinding()
	{
		$router = $this->getRouter();
		$router->get('foo/{bar}', function($name) { return $name; });
		$router->model('bar', 'RouteModelBindingStub');
		$this->assertEquals('TAYLOR', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testModelBindingWithNullReturn()
	{
		$router = $this->getRouter();
		$router->get('foo/{bar}', function($name) { return $name; });
		$router->model('bar', 'RouteModelBindingNullStub');
		$router->dispatch(Request::create('foo/taylor', 'GET'))->getContent();
	}


	public function testModelBindingWithCustomNullReturn()
	{
		$router = $this->getRouter();
		$router->get('foo/{bar}', function($name) { return $name; });
		$router->model('bar', 'RouteModelBindingNullStub', function() { return 'missing'; });
		$this->assertEquals('missing', $router->dispatch(Request::create('foo/taylor', 'GET'))->getContent());
	}


	public function testGroupMerging()
	{
		$old = array('prefix' => 'foo/bar/');
		$this->assertEquals(array('prefix' => 'foo/bar/baz', 'namespace' => null), Router::mergeGroup(array('prefix' => 'baz'), $old));

		$old = array('domain' => 'foo');
		$this->assertEquals(array('domain' => 'baz', 'prefix' => null, 'namespace' => null), Router::mergeGroup(array('domain' => 'baz'), $old));
	}


	public function testRouteGrouping()
	{
		/**
		 * Inhereting Filters
		 */
		$router = $this->getRouter();
		$router->group(array('before' => 'foo'), function() use ($router)
		{
			$router->get('foo/bar', function() { return 'hello'; });
		});
		$router->filter('foo', function() { return 'foo!'; });
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());


		/**
		 * Merging Filters
		 */
		$router = $this->getRouter();
		$router->group(array('before' => 'foo'), function() use ($router)
		{
			$router->get('foo/bar', array('before' => 'bar', function() { return 'hello'; }));
		});
		$router->filter('foo', function() {});
		$router->filter('bar', function() { return 'foo!'; });
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());


		/**
		 * Merging Filters
		 */
		$router = $this->getRouter();
		$router->group(array('before' => 'foo|bar'), function() use ($router)
		{
			$router->get('foo/bar', array('before' => 'baz', function() { return 'hello'; }));
		});
		$router->filter('foo', function() {});
		$router->filter('bar', function() {});
		$router->filter('baz', function() { return 'foo!'; });
		$this->assertEquals('foo!', $router->dispatch(Request::create('foo/bar', 'GET'))->getContent());
	}


	public function testMergingControllerUses()
	{
		$router = $this->getRouter();
		$router->group(array('namespace' => 'Namespace'), function() use ($router)
		{
			$router->get('foo/bar', 'Controller');
		});
		$routes = $router->getRoutes()->getRoutes();
		$action = $routes[0]->getAction();

		$this->assertEquals('Namespace\\Controller', $action['controller']);


		$router = $this->getRouter();
		$router->group(array('namespace' => 'Namespace'), function() use ($router)
		{
			$router->group(array('namespace' => 'Nested'), function() use ($router)
			{
				$router->get('foo/bar', 'Controller');
			});
		});
		$routes = $router->getRoutes()->getRoutes();
		$action = $routes[0]->getAction();

		$this->assertEquals('Namespace\\Nested\\Controller', $action['controller']);
	}


	public function testResourceRouting()
	{
		$router = $this->getRouter();
		$router->resource('foo', 'FooController');
		$routes = $router->getRoutes();
		$this->assertEquals(8, count($routes));

		$router = $this->getRouter();
		$router->resource('foo', 'FooController', array('only' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(2, count($routes));

		$router = $this->getRouter();
		$router->resource('foo', 'FooController', array('except' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(6, count($routes));

		$router = $this->getRouter();
		$router->resource('foo-bars', 'FooController', array('only' => array('show')));
		$routes = $router->getRoutes();
		$routes = $routes->getRoutes();

		$this->assertEquals('foo-bars/{foo_bars}', $routes[0]->getUri());

		$router = $this->getRouter();
		$router->resource('foo-bars.foo-bazs', 'FooController', array('only' => array('show')));
		$routes = $router->getRoutes();
		$routes = $routes->getRoutes();

		$this->assertEquals('foo-bars/{foo_bars}/foo-bazs/{foo_bazs}', $routes[0]->getUri());
	}


	public function testResourceRouteNaming()
	{
		$router = $this->getRouter();
		$router->resource('foo', 'FooController');

		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.index'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.show'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.create'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.store'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.edit'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.update'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.destroy'));

		$router = $this->getRouter();
		$router->resource('foo.bar', 'FooController');

		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.index'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.show'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.create'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.store'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.edit'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.update'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo.bar.destroy'));

		$router = $this->getRouter();
		$router->resource('foo', 'FooController', array('names' => array(
			'index' => 'foo',
			'show' => 'bar',
		)));

		$this->assertTrue($router->getRoutes()->hasNamedRoute('foo'));
		$this->assertTrue($router->getRoutes()->hasNamedRoute('bar'));
	}


	protected function getRouter()
	{
		return new Router(new Illuminate\Events\Dispatcher);
	}

}


class RouteTestControllerDispatchStub extends Illuminate\Routing\Controller {
	public function __construct()
	{
		$this->beforeFilter('foo', array('only' => 'bar'));
		$this->beforeFilter('@filter', array('only' => 'baz'));
	}
	public function foo()
	{
		return 'bar';
	}
	public function bar()
	{
		return 'baz';
	}
	public function filter()
	{
		return 'filtered';
	}
	public function baz()
	{
		return 'baz';
	}
}


class RouteModelBindingStub {
	public function find($value) { return strtoupper($value); }
}

class RouteModelBindingNullStub {
	public function find($value) {}
}

class RouteTestFilterStub {
	public function filter()
	{
		return 'foo!';
	}
	public function handle()
	{
		return 'handling!';
	}
}
