<?php

use Illuminate\Routing\Route;
use Illuminate\Container\Container;
use Illuminate\Routing\RouteCollection;

class RouteCollectionTest extends PHPUnit_Framework_TestCase {

	/** @var Illuminate\Routing\RouteCollection */
	protected $collection;

	/** @var Illuminate\Container\Container */
	protected $container;

	public function setUp()
	{
		$this->collection = new RouteCollection();
		$this->container = new Container();
		$this->container['foo'] = function () {};
	}

	public function testRouteSerializationWithDuplicatePathAndDifferingName()
	{
		// Create two routes with the same path and method
		$routeA = new Route('GET', 'product', ['uses' => 'View@view', 'as' => 'routeA']);
		$routeB = new Route('GET', 'product', ['uses' => 'View@view', 'as' => 'routeB']);

		// Add Routes to the collection, add the container to the routes
		foreach (compact('routeA', 'routeB') as $route)
		{
			$route->setContainer($this->container);
			$this->collection->add($route);
		}

		// Prepare the routes for serialization
		$this->collection->prepareForSerialization();

		// Serialize the Routes
		serialize($this->collection);
	}

	public function testRouteSerializationWithDuplicatePathAndDifferingMethod()
	{
		// Create two routes with the same path and method
		$routeA = new Route('GET', 'product', ['uses' => 'View@view']);
		$routeB = new Route('HEAD', 'product', ['uses' => 'View@view']);

		// Add Routes to the collection, add the container to the routes
		foreach (compact('routeA', 'routeB') as $route)
		{
			$route->setContainer($this->container);
			$this->collection->add($route);
		}

		// Prepare the routes for serialization
		$this->collection->prepareForSerialization();

		// Serialize the Routes
		serialize($this->collection);
	}

	public function testRouteSerializationWithDuplicatePathAndDifferingAction()
	{
		// Create two routes with the same path and method
		$routeA = new Route('GET', 'product', ['controller' => 'foo']);
		$routeB = new Route('GET', 'product', ['controller' => 'bar']);

		// Add Routes to the collection, add the container to the routes
		foreach (compact('routeA', 'routeB') as $route)
		{
			$route->setContainer($this->container);
			$this->collection->add($route);
		}

		// Prepare the routes for serialization
		$this->collection->prepareForSerialization();

		// Serialize the Routes
		serialize($this->collection);
	}

}
