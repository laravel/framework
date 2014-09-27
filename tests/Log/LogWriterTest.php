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


	public function testErrorLogHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type('Monolog\Handler\ErrorLogHandler'));
		$writer->useErrorLog();
	}


	public function testMethodsPassErrorAdditionsToMonolog()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$monolog->shouldReceive('error')->once()->with('foo', []);

		$writer->error('foo');
	}


	public function testWriterFiresEventsDispatcher()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'), $events = new Illuminate\Events\Dispatcher);
		$monolog->shouldReceive('error')->once()->with('foo', []);

		$events->listen('illuminate.log', function($level, $message, array $context = [])
		{
			$_SERVER['__log.level']   = $level;
			$_SERVER['__log.message'] = $message;
			$_SERVER['__log.context'] = $context;
		});

		$writer->error('foo');
		$this->assertTrue(isset($_SERVER['__log.level']));
		$this->assertEquals('error', $_SERVER['__log.level']);
		unset($_SERVER['__log.level']);
		$this->assertTrue(isset($_SERVER['__log.message']));
		$this->assertEquals('foo', $_SERVER['__log.message']);
		unset($_SERVER['__log.message']);
		$this->assertTrue(isset($_SERVER['__log.context']));
		$this->assertEquals([], $_SERVER['__log.context']);
		unset($_SERVER['__log.context']);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testListenShortcutFailsWithNoDispatcher()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'));
		$writer->listen(function() {});
	}


	public function testListenShortcut()
	{
		$writer = new Writer($monolog = m::mock('Monolog\Logger'), $events = m::mock('Illuminate\Contracts\Events\Dispatcher'));

		$callback = function() { return 'success'; };
		$events->shouldReceive('listen')->with('illuminate.log', $callback)->once();

		$writer->listen($callback);
	}

}
