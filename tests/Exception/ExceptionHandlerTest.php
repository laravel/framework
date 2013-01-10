<?php

use Illuminate\Exception\Handler;

class ExceptionHandlerTest extends PHPUnit_Framework_TestCase {

	public function testExceptionHandlerReturnsNullWhenNoHandlersHandleGivenException()
	{
		$handler = new Handler;
		$exception = new InvalidArgumentException;
		$callback = function(RuntimeException $e) {};
		$handler->error($callback);
		$this->assertNull($handler->handle($exception));
	}

	
	public function testExceptionHandlerReturnsResponseWhenHandlerFound()
	{
		$handler = new Handler;
		$exception = new RuntimeException;
		$callback = function(RuntimeException $e) { return 'foo'; };
		$handler->error($callback);
		$this->assertEquals('foo', $handler->handle($exception));
	}


	public function testGlobalHandlersAreCalled()
	{
		$handler = new Handler;
		$exception = new RuntimeException;
		$callback = function(Exception $e) { return 'foo'; };
		$handler->error($callback);
		$this->assertEquals('foo', $handler->handle($exception));
	}


	public function testAllHandlersAreCalled()
	{
		$_SERVER['__exception.handler'] = 0;
		$handler = new Handler;
		$exception = new RuntimeException;
		$callback1 = function($e) { $_SERVER['__exception.handler']++; };
		$callback2 = function($e) { $_SERVER['__exception.handler']++; };
		$handler->error($callback1);
		$handler->error($callback2);
		$handler->handle($exception);
		unset($_SERVER['__exception.handler']);
	}


	public function testFiveHundredCodeGivenOnNormalExceptions()
	{
		$handler = new Handler;
		$exception = new RuntimeException;
		$callback = function($e, $code) { return $code; };
		$handler->error($callback);
		$this->assertEquals(500, $handler->handle($exception));	
	}


	public function testHttpStatusCodeGivenOnHttpExceptions()
	{
		$handler = new Handler;
		$exception = new Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
		$callback = function($e, $code) { return $code; };
		$handler->error($callback);
		$this->assertEquals(404, $handler->handle($exception));
	}

}