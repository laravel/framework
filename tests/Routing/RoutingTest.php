<?php

use Mockery as m;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

class RoutingTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testBasic()
	{
		$router = new Router;
		$router->get('/', function() { return 'root'; });
		$router->get('/foo', function() { return 'bar'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('bar', $router->dispatch($request)->getContent());

		$request = Request::create('http://foo.com', 'GET');
		$this->assertEquals('root', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	public function testPrefixRouting()
	{
		$router = new Router;
		$router->get('/', array('prefix' => 'blog', function() { return 'root'; }));
		$router->get('/foo', array('prefix' => 'blog', function() { return 'bar'; }));

		$request = Request::create('/blog', 'GET');
		$this->assertEquals('root', $router->dispatch($request)->getContent());
		$request = Request::create('/blog/foo', 'GET');
		$this->assertEquals('bar', $router->dispatch($request)->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testBasicWithTrailingSlashNotRoot()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });

		$request = Request::create('/foo///', 'GET');
		$this->assertEquals('bar', $router->dispatch($request)->getContent());
	}


	public function testCurrentRequestAndRouteIsSetOnRouter()
	{
		$router = new Router;
		$route = $router->get('/foo', function() { return 'bar'; });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('bar', $router->dispatch($request)->getContent());
		$this->assertEquals($request, $router->getRequest());
		$this->assertEquals($route, $router->getCurrentRoute());
	}


	public function testVariablesCanBeRetrievedFromCurrentRouteInstance()
	{
		$router = new Router;
		$route = $router->get('/foo/{name}', function() { return 'bar'; });
		$request = Request::create('/foo/taylor', 'GET');

		$this->assertEquals('bar', $router->dispatch($request)->getContent());
		$this->assertEquals('taylor', $router->getCurrentRoute()->getParameter('name'));
	}


	public function testResourceRouting()
	{
		$router = new Router;
		$router->resource('foo', 'FooController');
		$routes = $router->getRoutes();

		$this->assertEquals(8, count($routes));

		$router = new Router;
		$router->resource('foo', 'FooController', array('only' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(2, count($routes));

		$router = new Router;
		$router->resource('foo', 'FooController', array('except' => array('show', 'destroy')));
		$routes = $router->getRoutes();

		$this->assertEquals(6, count($routes));
	}


	public function testResourceRouteNaming()
	{
		$router = new Router;
		$router->resource('foo', 'FooController');

		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.index'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.show'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.create'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.store'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.edit'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.update'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.destroy'));

		$router = new Router;
		$router->resource('foo.bar', 'FooController');

		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.index'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.show'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.create'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.store'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.edit'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.update'));
		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('foo.bar.destroy'));
	}


	public function testResourceRouteUriGeneration()
	{
		$router = new Router;
		$router->resource('foo.bar', 'FooController');

		$this->assertEquals('/foo/{foo}/bar', $router->getRoutes()->get('foo.bar.index')->getPath());
		$this->assertEquals('/foo/{foo}/bar/{bar}', $router->getRoutes()->get('foo.bar.show')->getPath());
		$this->assertEquals('/foo/{foo}/bar/create', $router->getRoutes()->get('foo.bar.create')->getPath());
		$this->assertEquals('/foo/{foo}/bar', $router->getRoutes()->get('foo.bar.store')->getPath());
		$this->assertEquals('/foo/{foo}/bar/{bar}/edit', $router->getRoutes()->get('foo.bar.edit')->getPath());
		$this->assertEquals('/foo/{foo}/bar/{bar}', $router->getRoutes()->get('foo.bar.update')->getPath());
		$this->assertEquals('/foo/{foo}/bar/{bar}', $router->getRoutes()->get('foo.bar.destroy')->getPath());

		$router->resource('admin/foo.baz', 'FooController');

		$this->assertEquals('/admin/foo/{foo}/baz', $router->getRoutes()->get('admin.foo.baz.index')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz/{baz}', $router->getRoutes()->get('admin.foo.baz.show')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz/create', $router->getRoutes()->get('admin.foo.baz.create')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz', $router->getRoutes()->get('admin.foo.baz.store')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz/{baz}/edit', $router->getRoutes()->get('admin.foo.baz.edit')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz/{baz}', $router->getRoutes()->get('admin.foo.baz.update')->getPath());
		$this->assertEquals('/admin/foo/{foo}/baz/{baz}', $router->getRoutes()->get('admin.foo.baz.destroy')->getPath());
	}


	public function testControllersAreCalledFromControllerRoutes()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'index', array('taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/taylor', 'GET');
		$router->get('/foo/{name}', 'home@index');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	public function testControllersAreCalledFromControllerRoutesWithUsesStatement()
	{
		$router = new Router;
		$container = m::mock('Illuminate\Container\Container');
		$controller = m::mock('stdClass');
		$controller->shouldReceive('callAction')->once()->with($container, $router, 'index', array('taylor'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('home')->andReturn($controller);
		$router->setContainer($container);
		$request = Request::create('/foo/taylor', 'GET');
		$router->get('/foo/{name}', array('uses' => 'home@index'));

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
	}


	public function testOptionalParameters()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age = null) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name}/{age?}', function($name, $age = null) { return $name.$age; });
		$request = Request::create('/foo/taylor', 'GET');
		$this->assertEquals('taylor', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name = null, $age = null) { return $name.$age; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('', $router->dispatch($request)->getContent());

		$router = new Router;
		$router->get('/foo/{name?}/{age?}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/25', 'GET');
		$this->assertEquals('taylor25', $router->dispatch($request)->getContent());
	}


	public function testGlobalBeforeFiltersHaltRequestCycle()
	{
		$router = new Router;
		$router->before(function() { return 'foo'; });
		$this->assertEquals('foo', $router->dispatch(Request::create('/bar', 'GET'))->getContent());

		$router = new Router($container = m::mock('Illuminate\Container\Container'));
		$filter = m::mock('stdClass');
		$filter->shouldReceive('filter')->once()->with(m::type('Symfony\Component\HttpFoundation\Request'))->andReturn('foo');
		$container->shouldReceive('make')->once()->with('FooFilter')->andReturn($filter);
		$router->before('FooFilter');
		$this->assertEquals('foo', $router->dispatch(Request::create('/bar', 'GET'))->getContent());		
	}


	public function testAfterAndCloseFiltersAreCalled()
	{
		$_SERVER['__routing.test'] = '';
		$router = new Router;
		$router->get('/foo', function() { return 'foo'; });
		$router->before(function() { return null; });
		$router->after(function() { $_SERVER['__routing.test'] = 'foo'; });
		$router->close(function() { $_SERVER['__routing.test'] .= 'bar'; });
		$request = Request::create('/foo', 'GET');
		
		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals('foobar', $_SERVER['__routing.test']);
		unset($_SERVER['__routing.test']);
	}


	public function testFinishFiltersCanBeCalled()
	{
		$_SERVER['__finish.test'] = false;
		$router = new Router;
		$router->finish(function() { $_SERVER['__finish.test'] = true; });
		$router->callFinishFilter(Request::create('/foo', 'GET'), new Illuminate\Http\Response);
		$this->assertTrue($_SERVER['__finish.test']);
		unset($_SERVER['__finish.test']);
	}


	public function testBeforeFiltersStopRequestCycle()
	{
		$router = new Router;
		$router->get('/foo', array('before' => 'filter|filter-2', function() { return 'foo'; }));
		$router->addFilter('filter', function() { return 'filtered!'; });
		$router->addFilter('filter-2', function() { return null; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->getContent());
	}


	public function testBeforeFiltersArePassedRouteAndRequest()
	{
		unset($_SERVER['__before.args']);
		$router = new Router;
		$route = $router->get('/foo', array('before' => 'filter', function() { return 'foo'; }));
		$router->addFilter('filter', function() { $_SERVER['__before.args'] = func_get_args(); });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals($route, $_SERVER['__before.args'][0]);
		$this->assertEquals($request, $_SERVER['__before.args'][1]);
		unset($_SERVER['__before.args']);
	}


	public function testBeforeFiltersArePassedRouteAndRequestAndCustomParameters()
	{
		unset($_SERVER['__before.args']);
		$router = new Router;
		$route = $router->get('/foo', array('before' => 'filter:dayle,rees', function() { return 'foo'; }));
		$router->addFilter('filter', function() { $_SERVER['__before.args'] = func_get_args(); });
		$request = Request::create('/foo', 'GET');

		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertEquals($route, $_SERVER['__before.args'][0]);
		$this->assertEquals($request, $_SERVER['__before.args'][1]);
		$this->assertEquals('dayle', $_SERVER['__before.args'][2]);
		$this->assertEquals('rees', $_SERVER['__before.args'][3]);
		unset($_SERVER['__before.args']);
	}


	public function testPatternFiltersAreCalledBeforeRoute()
	{
		$router = new Router;
		$router->get('/foo', function() { return 'bar'; });
		$router->matchFilter('bar*', 'something');
		$router->matchFilter('f*', 'filter');
		$router->addFilter('filter', function() { return 'filtered!'; });
		$router->addFilter('something', function() { return 'something'; });
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('filtered!', $router->dispatch($request)->getContent());
	}


	public function testAfterMiddlewaresAreCalled()
	{
		$router = new Router;
		$_SERVER['__filter.after'] = false;
		$router->addFilter('filter', function() { return $_SERVER['__filter.after'] = true; });
		$router->get('/foo', array('after' => 'filter', function() { return 'foo'; }));
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertTrue($_SERVER['__filter.after']);
		unset($_SERVER['__filter.after']);
	}


	public function testAfterMiddlewaresAreNotCalledWhenRouteIsNotCalled()
	{
		$router = new Router;
		$_SERVER['__filter.before'] = false;
		$_SERVER['__filter.after'] = false;
		$router->addFilter('before-filter', function() { $_SERVER['__filter.before'] = true; return 'foo'; });
		$router->addFilter('after-filter', function() { $_SERVER['__filter.after'] = true; });
		$router->get('/foo', array('before' => 'before-filter', 'after' => 'after-filter', function() { return 'bar'; }));
		$request = Request::create('/foo', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->getContent());
		$this->assertTrue($_SERVER['__filter.before']);
		$this->assertFalse($_SERVER['__filter.after']);
		unset($_SERVER['__filter.before']);
		unset($_SERVER['__filter.after']);
	}


	public function testAfterMiddlewaresAreCalledWithProperArguments()
	{
		$router = new Router;
		$_SERVER['__filter.after'] = false;
		$router->addFilter('filter', function() { return $_SERVER['__after.args'] = func_get_args(); });
		$route = $router->get('/foo', array('after' => 'filter:dayle,rees', function() { return 'foo'; }));
		$request = Request::create('/foo', 'GET');

		$response = $router->dispatch($request);
		$this->assertEquals('foo', $response->getContent());
		$this->assertEquals($route, $_SERVER['__after.args'][0]);
		$this->assertEquals($request, $_SERVER['__after.args'][1]);
		$this->assertEquals($response, $_SERVER['__after.args'][2]);
		$this->assertEquals('dayle', $_SERVER['__after.args'][3]);
		$this->assertEquals('rees', $_SERVER['__after.args'][4]);
		unset($_SERVER['__after.args']);
	}


	public function testFiltersCanBeDisabled()
	{
		$router = new Router;
		$router->disableFilters();
		$router->get('foo', array('before' => 'route-before', function()
		{
			return 'hello world';
		}));
		$router->before(function() { $_SERVER['__filter.test'] = true; });
		$router->addFilter('route-before', function() { $_SERVER['__filter.test'] = true; });
		$router->matchFilter('foo', 'route-before');
		$router->after(function() { $_SERVER['__filter.test'] = true; });

		$request = Request::create('/foo', 'GET');
		$this->assertEquals('hello world', $router->dispatch($request)->getContent());
		$this->assertFalse(isset($_SERVER['__filter.test']));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testWhereMethodForcesRegularExpressionMatch()
	{
		$router = new Router;
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; })->where('age', '[0-9]+');
		$request = Request::create('/foo/taylor/abc', 'GET');
		$this->assertEquals('taylorabc', $router->dispatch($request)->getContent());
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testGlobalParameterPatternsAreApplied()
	{
		$router = new Router;
		$router->pattern('age', '[0-9]+');
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/abc', 'GET');
		$this->assertEquals('taylorabc', $router->dispatch($request)->getContent());
	}


	public function testGlobalParameterPatternsDontInterfereWithRoutesTheyDontApplyTo()
	{
		$router = new Router;
		$router->pattern('age', '[0-9]+');
		$router->get('/foo/{name}/{age}', function($name, $age) { return $name.$age; });
		$request = Request::create('/foo/taylor/123', 'GET');
		$this->assertEquals('taylor123', $router->dispatch($request)->getContent());
	}


	public function testBeforeFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->before('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getBeforeFilters());
	}


	public function testAfterFiltersCanBeSetOnRoute()
	{
		$route = new Route('/foo');
		$route->after('foo', 'bar');
		$this->assertEquals(array('foo', 'bar'), $route->getAfterFilters());
	}


	public function testGroupCanShareAttributesAcrossRoutes()
	{
		$router = new Router;
		$router->group(array('before' => 'foo'), function() use ($router)
		{
			$router->get('foo', function() {});
			$router->get('bar', array('before' => 'bar', function() {}));
		});
		$routes = array_values($router->getRoutes()->getIterator()->getArrayCopy());

		$this->assertEquals(array('foo'), $routes[0]->getOption('_before'));
		$this->assertEquals(array('foo', 'bar'), $routes[1]->getOption('_before'));
	}


	public function testStringFilterAreResolvedOutOfTheContainer()
	{
		$router = new Router($container = m::mock('Illuminate\Container\Container'));
		$router->addFilter('foo', 'FooFilter');
		$container->shouldReceive('make')->once()->with('FooFilter')->andReturn('bar');

		$this->assertEquals(array('bar', 'filter'), $router->getFilter('foo'));
	}


	public function testStringFilterAreResolvedOutOfTheContainerWithCustomMethods()
	{
		$router = new Router($container = m::mock('Illuminate\Container\Container'));
		$router->addFilter('foo', 'FooFilter@something');
		$container->shouldReceive('make')->once()->with('FooFilter')->andReturn('bar');

		$this->assertEquals(array('bar', 'something'), $router->getFilter('foo'));
	}


	public function testCurrentRouteNameCanBeChecked()
	{
		$router = new Router(new Illuminate\Container\Container);
		$route = $router->get('foo', array('as' => 'foo.route', function() {}));
		$route2 = $router->get('bar', array('as' => 'bar.route', function() {}));
		$router->setCurrentRoute($route);

		$this->assertTrue($router->currentRouteNamed('foo.route'));
		$this->assertFalse($router->currentRouteNamed('bar.route'));
	}


	public function testCurrentRouteNameCanBeRetrieved()
	{
		$router = new Router(new Illuminate\Container\Container);
		$route = $router->get('foo', array('as' => 'foo.route', function() {}));
		$router->setCurrentRoute($route);

		$this->assertEquals('foo.route', $router->currentRouteName());
	}


	public function testCurrentRouteActionCanBeChecked()
	{
		$router = new Router(new Illuminate\Container\Container);
		$route = $router->get('foo', array('uses' => 'foo.route@action'));
		$route2 = $router->get('bar', array('uses' => 'bar.route@action'));
		$router->setCurrentRoute($route);

		$this->assertTrue($router->currentRouteUses('foo.route@action'));
		$this->assertFalse($router->currentRouteUses('bar.route@action'));
	}


	public function testControllerMethodProperlyRegistersRoutes()
	{
		$router = $this->getMock('Illuminate\Routing\Router', array('get', 'any'), array(new Illuminate\Container\Container));
		$router->setInspector($inspector = m::mock('Illuminate\Routing\Controllers\Inspector'));
		$inspector->shouldReceive('getRoutable')->once()->with('FooController', 'prefix')->andReturn(array(
			'getFoo' => array(
				array('verb' => 'get', 'uri' => 'foo'),
			)
		));
		$router->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo('FooController@getFoo'));
		$router->expects($this->once())->method('any')->with($this->equalTo('prefix/{_missing}'), $this->equalTo('FooController@missingMethod'))->will($this->returnValue($missingRoute = m::mock('StdClass')));
		$missingRoute->shouldReceive('where')->once()->with('_missing', '(.*)');

		$router->controller('prefix', 'FooController');
	}


	public function testRouteParameterBinding()
	{
		$router = new Router(new Illuminate\Container\Container);
		$router->bind('user', function($value) { return $value.'-bar'; });
		$router->get('user/{user}', function($user) { return $user; });
		$request = Request::create('/user/foo', 'GET');
		$this->assertEquals('foo-bar', $router->dispatch($request)->getContent());

		$router = new Router(new Illuminate\Container\Container);
		$router->model('user', 'RoutingModelBindingStub');
		$router->get('user/{user}', function($user) { return $user; });
		$request = Request::create('/user/foo', 'GET');
		$this->assertEquals('foo', $router->dispatch($request)->getContent());

		$router = new Router(new Illuminate\Container\Container);
		$router->model('user', 'RoutingModelBindingStub');
		$router->get('user/{user?}', function($user = 'default') { return $user; });
		$request = Request::create('/user', 'GET');
		$this->assertEquals('default', $router->dispatch($request)->getContent());
	}


	public function testRoutesArentOverriddenBySubDomain()
	{
		$router = new Router(new Illuminate\Container\Container);
		$router->get('/', array('domain' => 'foo.com', function() { return 'main'; }));
		$router->get('/', array('domain' => 'bar.com', function() { return 'sub'; }));

		$request = Request::create('http://foo.com', 'GET');
		$this->assertEquals('main', $router->dispatch($request)->getContent());

		$request = Request::create('http://bar.com', 'GET');
		$this->assertEquals('sub', $router->dispatch($request)->getContent());
	}
	

	public function testRoutesArentOverriddenBySubDomainWithGroups()
	{
		$router = new Router(new Illuminate\Container\Container);
		$router->group(array('domain' => 'foo.com'), function() use ($router)
		{
			$router->get('/', function() { return 'main'; });
		});
		$router->group(array('domain' => 'bar.com'), function() use ($router)
		{
			$router->get('/', function() { return 'sub'; });
		});
		
		$request = Request::create('http://foo.com', 'GET');
		$this->assertEquals('main', $router->dispatch($request)->getContent());

		$request = Request::create('http://bar.com', 'GET');
		$this->assertEquals('sub', $router->dispatch($request)->getContent());
	}


	public function testNestedGroupRoutesInheritAllSettings()
	{
		$router = new Router(new Illuminate\Container\Container);
		$router->group(array('before' => 'foo'), function() use ($router)
		{
			$router->group(array('before' => 'bar'), function() use ($router)
			{
				$router->get('/', function() { return 'baz'; });
			});
		});

		$this->assertEquals(array('foo', 'bar'), $router->getRoutes()->get('get /')->getOption('_before'));
	}


	public function testNestedPrefixedRoutes()
	{
		$router = new Router(new Illuminate\Container\Container);
		$router->group(array('prefix' => 'first'), function() use ($router)
		{
			$router->group(array('prefix' => 'second'), function() use ($router)
			{
				$router->get('third', function() {});
			});
		});

		$this->assertInstanceOf('Illuminate\Routing\Route', $router->getRoutes()->get('get first/second/third'));
	}

}

class RoutingModelBindingStub {
	public function find($value) { return $value; }
}