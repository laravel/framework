<?php

use Mockery as m;
use Illuminate\Pagination\Factory;

class PaginationFactoryTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreationOfEnvironment()
	{
		$env = $this->getFactory();
	}


	public function testPaginatorCanBeCreated()
	{
		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com', 'GET');
		$env->setRequest($request);

		$this->assertInstanceOf('Illuminate\Pagination\Paginator', $env->make(array('foo', 'bar'), 2, 2));
	}


	public function testPaginationViewCanBeCreated()
	{
		$env = $this->getFactory();
		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$env->getViewFactory()->shouldReceive('make')->once()->with('pagination::slider', array('environment' => $env, 'paginator' => $paginator))->andReturn('foo');

		$this->assertEquals('foo', $env->getPaginationView($paginator));
	}


	public function testCurrentPageCanBeRetrieved()
	{
		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals(2, $env->getCurrentPage());

		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com?page=-1', 'GET');
		$env->setRequest($request);

		$this->assertEquals(1, $env->getCurrentPage());
	}


	public function testSettingCurrentUrlOverrulesRequest()
	{
		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);
		$env->setCurrentPage(3);

		$this->assertEquals(3, $env->getCurrentPage());
	}


	public function testCurrentUrlCanBeRetrieved()
	{
		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com/bar?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals('http://foo.com/bar', $env->getCurrentUrl());

		$env = $this->getFactory();
		$request = Illuminate\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals('http://foo.com', $env->getCurrentUrl());
	}


	public function testOverridingPageParam()
	{
		$env = $this->getFactory();
		$this->assertEquals('page', $env->getPageName());
		$env->setPageName('foo');
		$this->assertEquals('foo', $env->getPageName());
	}


	protected function getFactory()
	{
		$request = m::mock('Illuminate\Http\Request');
		$view = m::mock('Illuminate\View\Factory');
		$trans = m::mock('Symfony\Component\Translation\TranslatorInterface');
		$view->shouldReceive('addNamespace')->once()->with('pagination', realpath(__DIR__.'/../../src/Illuminate/Pagination').'/views');

		return new Factory($request, $view, $trans, 'page');
	}

}
