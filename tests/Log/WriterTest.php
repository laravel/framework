<?php

use Mockery as m;
use Illuminate\Log\Writer;

class WriterTest extends PHPUnit_Framework_TestCase {

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

}