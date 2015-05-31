<?php

use Mockery as m;
use Monolog\Logger;
use Illuminate\Log\Writer;
use Illuminate\Events\Dispatcher;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

class LogWriterTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFileHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock(Logger::class));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type(StreamHandler::class));
		$writer->useFiles(__DIR__);
	}


	public function testRotatingFileHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock(Logger::class));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type(RotatingFileHandler::class));
		$writer->useDailyFiles(__DIR__, 5);
	}


	public function testErrorLogHandlerCanBeAdded()
	{
		$writer = new Writer($monolog = m::mock(Logger::class));
		$monolog->shouldReceive('pushHandler')->once()->with(m::type(ErrorLogHandler::class));
		$writer->useErrorLog();
	}


	public function testMethodsPassErrorAdditionsToMonolog()
	{
		$writer = new Writer($monolog = m::mock(Logger::class));
		$monolog->shouldReceive('error')->once()->with('foo', []);

		$writer->error('foo');
	}


	public function testWriterFiresEventsDispatcher()
	{
		$writer = new Writer($monolog = m::mock(Logger::class), $events = new Dispatcher);
		$monolog->shouldReceive('error')->once()->with('foo', array());

		$events->listen('illuminate.log', function($level, $message, array $context = array())
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
		$this->assertEquals(array(), $_SERVER['__log.context']);
		unset($_SERVER['__log.context']);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testListenShortcutFailsWithNoDispatcher()
	{
		$writer = new Writer($monolog = m::mock(Logger::class));
		$writer->listen(function() {});
	}


	public function testListenShortcut()
	{
		$writer = new Writer($monolog = m::mock(Logger::class), $events = m::mock(DispatcherContract::class));

		$callback = function() { return 'success'; };
		$events->shouldReceive('listen')->with('illuminate.log', $callback)->once();

		$writer->listen($callback);
	}

}
