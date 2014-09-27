<?php

use Mockery as m;

class FoundationEnvironmentDetectorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEnvironmentDetection()
	{
		$env = m::mock('Illuminate\Foundation\EnvironmentDetector')->makePartial();
		$env->shouldReceive('isMachine')->once()->with('localhost')->andReturn(false);
		$result = $env->detect([
			'local'   => ['localhost']
		]);
		$this->assertEquals('production', $result);


		$env = m::mock('Illuminate\Foundation\EnvironmentDetector')->makePartial();
		$env->shouldReceive('isMachine')->once()->with('localhost')->andReturn(true);
		$result = $env->detect([
			'local'   => ['localhost']
		]);
		$this->assertEquals('local', $result);
	}


	public function testClosureCanBeUsedForCustomEnvironmentDetection()
	{
		$env = new Illuminate\Foundation\EnvironmentDetector;

		$result = $env->detect(function() { return 'foobar'; });
		$this->assertEquals('foobar', $result);
	}


	public function testConsoleEnvironmentDetection()
	{
		$env = new Illuminate\Foundation\EnvironmentDetector;

		$result = $env->detect([
			'local'   => ['foobar']
		], ['--env=local']);
		$this->assertEquals('local', $result);
	}

}
