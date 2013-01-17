<?php

use Mockery as m;

class RoutingFilterTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicPropertiesAreSet()
	{
		$annotation = new Illuminate\Routing\Controllers\Filter(array('run' => 'foo', 'on' => array('post')));
		$this->assertEquals('foo', $annotation->run);
		$this->assertEquals(array('post'), $annotation->on);
	}


	public function testHeadIsAddedToOnWhenGetMethodIsIncluded()
	{
		$annotation = new Illuminate\Routing\Controllers\Filter(array('on' => array('get', 'post')));
		$this->assertTrue(in_array('head', $annotation->on));
	}


	public function testApplicableExcludesByRequestMethod()
	{
		$annotation = new Illuminate\Routing\Controllers\Filter(array('on' => 'post'));
		$request = m::mock('Symfony\Component\HttpFoundation\Request');
		$request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->assertFalse($annotation->applicable($request, 'foo'));

		$request2 = m::mock('Symfony\Component\HttpFoundation\Request');
		$request2->shouldReceive('getMethod')->once()->andReturn('post');
		$this->assertTrue($annotation->applicable($request2, 'foo'));
	}


	public function testApplicableExcludesByOnlyRule()
	{
		$annotation = new Illuminate\Routing\Controllers\Filter(array('only' => 'foo'));
		$request = m::mock('Symfony\Component\HttpFoundation\Request');
		$request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->assertFalse($annotation->applicable($request, 'foo-bar'));

		$request2 = m::mock('Symfony\Component\HttpFoundation\Request');
		$request2->shouldReceive('getMethod')->once()->andReturn('get');
		$this->assertTrue($annotation->applicable($request2, 'foo'));
	}


	public function testApplicableExcludesByExceptRule()
	{
		$annotation = new Illuminate\Routing\Controllers\Filter(array('except' => 'foo'));
		$request = m::mock('Symfony\Component\HttpFoundation\Request');
		$request->shouldReceive('getMethod')->once()->andReturn('get');
		$this->assertTrue($annotation->applicable($request, 'foo-bar'));

		$request2 = m::mock('Symfony\Component\HttpFoundation\Request');
		$request2->shouldReceive('getMethod')->once()->andReturn('get');
		$this->assertFalse($annotation->applicable($request2, 'foo'));
	}

}