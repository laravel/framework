<?php

use Mockery as m;
use Illuminate\Pagination\Factory;

class PaginationFactoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreationOfEnvironment()
	{
		$factory = $this->getFactoryProper();
		$this->assertInstanceOf('Illuminate\Pagination\Factory', $factory);
	}


	public function testPaginatorCanBeCreated()
	{
		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com', 'GET');
		$factory->setRequest($request);

		$this->assertInstanceOf('Illuminate\Pagination\Paginator', $factory->make(array('foo', 'bar'), 2, 2));
	}


	public function testPaginationViewCanBeCreated()
	{
		$factory = $this->getFactoryProper();
		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$factory->getViewFactory()->shouldReceive('make')->once()->with('pagination::slider', array('environment' => $factory, 'paginator' => $paginator))->andReturn('foo');

		$this->assertEquals('foo', $factory->getPaginationView($paginator));
	}


	public function testCurrentPageCanBeRetrieved()
	{
		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$factory->setRequest($request);

		$this->assertEquals(2, $factory->getCurrentPage());

		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com?page=-1', 'GET');
		$factory->setRequest($request);

		$this->assertEquals(1, $factory->getCurrentPage());
	}

	public function testSettingCurrentUrlOverrulesRequest()
	{
		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$factory->setRequest($request);
		$factory->setCurrentPage(3);

		$this->assertEquals(3, $factory->getCurrentPage());
	}


	public function testCurrentUrlCanBeRetrieved()
	{
		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com/bar?page=2', 'GET');
		$factory->setRequest($request);

		$this->assertEquals('http://foo.com/bar', $factory->getCurrentUrl());

		$factory = $this->getFactoryProper();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$factory->setRequest($request);

		$this->assertEquals('http://foo.com', $factory->getCurrentUrl());
	}


	public function testOverridingPageParam()
	{
		$factory = $this->getFactoryProper();
		$this->assertEquals('page', $factory->getPageName());
		$factory->setPageName('foo');
		$this->assertEquals('foo', $factory->getPageName());
	}


	protected function getFactoryProper()
	{
		$request = m::mock('Illuminate\Http\Request');
		$view = m::mock('Illuminate\View\Factory');
		$view->shouldReceive('addNamespace')->once()->with('pagination', realpath(__DIR__.'/../../src/Illuminate/Pagination').'/views');
		$trans = m::mock('Symfony\Component\Translation\TranslatorInterface');

		$factory = new Factory('page');
		$factory->setRequest($request);
		$factory->setTranslator($trans);
		$factory->setupPaginationEnvironment($view);

		return $factory;
	}

}
