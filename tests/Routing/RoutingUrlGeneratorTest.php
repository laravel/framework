<?php

use Illuminate\Routing\UrlGenerator;

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
		 * Test HTTPS request URL geneation...
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
		 * HTTPS...
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('as' => 'baz', 'https'));
		$routes->add($route);

		/**
		 * Controller Route Route
		 */
		$route = new Illuminate\Routing\Route(array('GET'), 'foo/bar', array('controller' => 'foo@bar'));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
		$this->assertEquals('http://www.foo.com/foo/bar/taylor/breeze/otwell?fly=wall', $url->route('bar', array('taylor', 'otwell', 'fly' => 'wall')));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->route('baz'));
		$this->assertEquals('http://www.foo.com/foo/bar', $url->action('foo@bar'));
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

}