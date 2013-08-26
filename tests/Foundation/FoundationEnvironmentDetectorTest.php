<?php

use Mockery as m;

class FoundationEnvironmentDetectorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEnvironmentDetection()
	{
		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('foo');
		$request->server = m::mock('StdClass');
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('production', $result);

		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('localhost');
		$request->server = m::mock('StdClass');
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(array(
			'local'   => array('localhost')
		));
		$this->assertEquals('local', $result);

		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('localhost');
		$request->server = m::mock('StdClass');
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(array(
			'local'   => array('local*')
		));
		$this->assertEquals('local', $result);

		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('localhost');
		$request->server = m::mock('StdClass');
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(array(
			'local'   => array(gethostname())
		));
		$this->assertEquals('local', $result);
	}


	public function testClosureCanBeUsedForCustomEnvironmentDetection()
	{
		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('foo');
		$request->server = m::mock('StdClass');
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(function() { return 'foobar'; });
		$this->assertEquals('foobar', $result);
	}


	public function testConsoleEnvironmentDetection()
	{
		$request = m::mock('Illuminate\Http\Request');
		$request->shouldReceive('getHost')->andReturn('foo');
		$request->server = m::mock('StdClass');
		$request->server->shouldReceive('get')->once()->with('argv')->andReturn(array('--env=local'));
		$env = new Illuminate\Foundation\EnvironmentDetector($request);

		$result = $env->detect(array(
			'local'   => array('foobar')
		), true);
		$this->assertEquals('local', $result);
	}

}