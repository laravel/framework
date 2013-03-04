<?php

use Mockery as m;
use Illuminate\Log\Writer;

class LogWriterTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFileHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\StreamHandler'));
		$writer->useFiles(__DIR__);
	}


	public function testRotatingFileHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\RotatingFileHandler'));
		$writer->useDailyFiles(__DIR__, 5);
	}


	public function testMagicMethodsPassErrorAdditionsToMonolog()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('addError')->once()->with('foo')->andReturn('bar');

		$this->assertEquals('bar', $writer->error('foo'));
	}


	public function testListening()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('addError')->once()->with('foo');

		$writer->listen(function($level, $parameters)
		{
			$_SERVER['__log.level']      = $level;
			$_SERVER['__log.parameters'] = $parameters;
		});

		$writer->error('foo');
		$this->assertTrue(isset($_SERVER['__log.level']));
		$this->assertEquals('error', $_SERVER['__log.level']);
		unset($_SERVER['__log.level']);
		$this->assertTrue(isset($_SERVER['__log.parameters']));
		$this->assertEquals(array('foo'), $_SERVER['__log.parameters']);
		unset($_SERVER['__log.parameters']);
	}


	public function testWriterFiresEventsDispatcher()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'), $events = new Illuminate\Events\Dispatcher);
		$monolog->shouldReceive('addError')->once()->with('foo');

		$events->listen('illuminate.log', function($level, $parameters)
		{
			$_SERVER['__log.level']      = $level;
			$_SERVER['__log.parameters'] = $parameters;
		});

		$writer->error('foo');
		$this->assertTrue(isset($_SERVER['__log.level']));
		$this->assertEquals('error', $_SERVER['__log.level']);
		unset($_SERVER['__log.level']);
		$this->assertTrue(isset($_SERVER['__log.parameters']));
		$this->assertEquals(array('foo'), $_SERVER['__log.parameters']);
		unset($_SERVER['__log.parameters']);
	}

}
