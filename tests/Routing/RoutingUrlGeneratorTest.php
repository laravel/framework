<?php

use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\UrlRoutable;

class RoutingUrlGeneratorTest extends PHPUnit_Framework_TestCase {

	public function testBasicGeneration()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->to('foo/bar'));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar', array(), true));
		$this->assertEquals('https://www.foo.com/foo/bar/baz/boom', $url->to('foo/bar', array('baz', 'boom'), true));

		/**
		 * Test HTTPS request URL generation...
		 */
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('https://www.foo.com/')
		);

		$this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar'));

		/**
		 * Test asset URL generation...
		 */
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/index.php/')
		);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->asset('foo/bar'));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->asset('foo/bar', true));
	}


	public function testBasicRouteGeneration()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		/**
		 * Empty Named Route
		 */
		$route = new Illuminate\Routing\Route(array('GET'), '/', array('as' => 'plain'));
		$routes->add($route);

		/**
		 * Named Routes
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo'));
		$routes->add($route);

		/**
		 * Parameters...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar/{baz}/breeze/{boom}', array('as' => 'bar'));
		$routes->add($route);

		/**
		 * Single Parameter...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar/{baz}', array('as' => 'foobar'));
		$routes->add($route);

		/**
		 * HTTPS...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'baz', 'https'));
		$routes->add($route);

		/**
		 * Controller Route Route
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('controller' => 'foo@bar'));
		$routes->add($route);

		/**
		 * Non ASCII routes
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar/åαф/{baz}', array('as' => 'foobarbaz'));
		$routes->add($route);

		/**
		 * Fragments
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar#derp', array('as' => 'fragment'));
		$routes->add($route);

		$this->assertEquals('/', $url->route('plain', array(), false));
		$this->assertEquals('/?foo=bar', $url->route('plain', array('foo' => 'bar'), false));
		$this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
		$this->assertEquals('/foo/bar', $url->route('foo', array(), false));
		$this->assertEquals('/foo/bar?foo=bar', $url->route('foo', array('foo' => 'bar'), false));
		$this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', array('taylor', 'otwell', 'fly' => 'wall')));
		$this->assertEquals('http://www.foo.com/foo/bar/otwell/breeze/taylor?fly=wall', $url->route('bar', array('boom' => 'taylor', 'baz' => 'otwell', 'fly' => 'wall')));
		$this->assertEquals('http://www.foo.com/foo/bar/2', $url->route('foobar', 2));
		$this->assertEquals('http://www.foo.com/foo/bar/taylor', $url->route('foobar', 'taylor'));
		$this->assertEquals('/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', array('taylor', 'otwell', 'fly' => 'wall'), false));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->route('baz'));
		$this->assertEquals('http://www.foo.com/foo/bar', $url->action('foo@bar'));
		$this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', array('wall', 'woz', 'boom' => 'otwell', 'baz' => 'taylor')));
		$this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?wall&woz', $url->route('bar', array('taylor', 'otwell', 'wall', 'woz')));
		$this->assertEquals('http://www.foo.com/foo/bar/%C3%A5%CE%B1%D1%84/%C3%A5%CE%B1%D1%84', $url->route('foobarbaz', array('baz' => 'åαф')));
		$this->assertEquals('/foo/bar#derp', $url->route('fragment', array(), false));
		$this->assertEquals('/foo/bar?foo=bar#derp', $url->route('fragment', array('foo' => 'bar'), false));
		$this->assertEquals('/foo/bar?baz=%C3%A5%CE%B1%D1%84#derp', $url->route('fragment', array('baz' => 'åαф'), false));
	}


	public function testControllerRoutesWithADefaultNamespace()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$url->setRootControllerNamespace('namespace');

		/**
		 * Controller Route Route
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('controller' => 'namespace\foo@bar'));
		$routes->add($route);

		$route = new Illuminate\Routing\Route(array('GET'), 'something/else', array('controller' => 'something\foo@bar'));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->action('foo@bar'));
		$this->assertEquals('http://www.foo.com/something/else', $url->action('\something\foo@bar'));
	}


	public function testRoutableInterfaceRouting()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$route = new Illuminate\Routing\Route(array('GET'), 'foo/{bar}', array('as' => 'routable'));
		$routes->add($route);

		$model = new RoutableInterfaceStub;
		$model->key = 'routable';

		$this->assertEquals('/foo/routable', $url->route('routable', [$model], false));
	}


	public function testRoutableInterfaceRoutingWithSingleParameter()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$route = new Illuminate\Routing\Route(array('GET'), 'foo/{bar}', array('as' => 'routable'));
		$routes->add($route);

		$model = new RoutableInterfaceStub;
		$model->key = 'routable';

		$this->assertEquals('/foo/routable', $url->route('routable', $model, false));
	}


	public function testRoutesMaintainRequestScheme()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('https://www.foo.com/')
		);

		/**
		 * Named Routes
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo'));
		$routes->add($route);

		$this->assertEquals('https://www.foo.com/foo/bar', $url->route('foo'));
	}


	public function testHttpOnlyRoutes()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('https://www.foo.com/')
		);

		/**
		 * Named Routes
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'http'));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
	}


	public function testRoutesWithDomains()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'domain' => 'sub.foo.com'));
		$routes->add($route);

		/**
		 * Wildcards & Domains...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar/{baz}', array('as' => 'bar', 'domain' => 'sub.{foo}.com'));
		$routes->add($route);

		$this->assertEquals('http://sub.foo.com/foo/bar', $url->route('foo'));
		$this->assertEquals('http://sub.taylor.com/foo/bar/otwell', $url->route('bar', array('taylor', 'otwell')));
		$this->assertEquals('/foo/bar/otwell', $url->route('bar', array('taylor', 'otwell'), false));
	}


	public function testRoutesWithDomainsAndPorts()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com:8080/')
		);

		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'domain' => 'sub.foo.com'));
		$routes->add($route);

		/**
		 * Wildcards & Domains...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar/{baz}', array('as' => 'bar', 'domain' => 'sub.{foo}.com'));
		$routes->add($route);

		$this->assertEquals('http://sub.foo.com:8080/foo/bar', $url->route('foo'));
		$this->assertEquals('http://sub.taylor.com:8080/foo/bar/otwell', $url->route('bar', array('taylor', 'otwell')));
	}


	public function testHttpsRoutesWithDomains()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('https://foo.com/')
		);

		/**
		 * When on HTTPS, no need to specify 443
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'baz', 'domain' => 'sub.foo.com'));
		$routes->add($route);

		$this->assertEquals('https://sub.foo.com/foo/bar', $url->route('baz'));
	}


	public function testUrlGenerationForControllers()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com:8080/')
		);

		$route = new Illuminate\Routing\Route(array('GET'), 'foo/{one}/{two?}/{three?}', array('as' => 'foo', function() {}));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com:8080/foo', $url->route('foo'));
	}


	public function testForceRootUrl()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$url->forceRootUrl('https://www.bar.com');
		$this->assertEquals('http://www.bar.com/foo/bar', $url->to('foo/bar'));

		/**
		 * Ensure trailing / is trimmed from root URL as UrlGenerator already handles this
		 */
		$url->forceRootUrl('http://www.foo.com/');
		$this->assertEquals('http://www.foo.com/bar', $url->to('/bar'));

		/**
		 * Route Based...
		 */
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$url->forceSchema('https');
		$route = new Illuminate\Routing\Route(array('GET'), '/foo', array('as' => 'plain'));
		$routes->add($route);

		$this->assertEquals('https://www.foo.com/foo', $url->route('plain'));

		$url->forceRootUrl('https://www.bar.com');
		$this->assertEquals('https://www.bar.com/foo', $url->route('plain'));
	}


	public function testPrevious()
	{
		$url = new UrlGenerator(
			$routes = new Illuminate\Routing\RouteCollection,
			$request = Illuminate\Http\Request::create('http://www.foo.com/')
		);

		$url->getRequest()->headers->set('referer', 'http://www.bar.com/');
		$this->assertEquals('http://www.bar.com/', $url->previous());

		$url->getRequest()->headers->remove('referer');
		$this->assertEquals($url->to('/'), $url->previous());
	}

}

class RoutableInterfaceStub implements UrlRoutable {
	public $key;
	public function getRouteKey() { return $this->{$this->getRouteKeyName()}; }
	public function getRouteKeyName() { return 'key'; }
}
