<?php

use Illuminate\Exception\Handler;
use Mockery as m;

class HandlerTest extends TestCase
{
	protected function setUp()
	{
		$this->responsePreparer = m::mock('Illuminate\Support\Contracts\ResponsePreparerInterface');
		$this->plainDisplayer = m::mock('Illuminate\Exception\ExceptionDisplayerInterface');
		$this->debugDisplayer = m::mock('Illuminate\Exception\ExceptionDisplayerInterface');
		$this->handler = new Handler($this->responsePreparer, $this->plainDisplayer, $this->debugDisplayer);
	}


	public function testHandleErrorExceptionArguments()
	{
		$error = null;
		try {
			$this->handler->handleError(E_USER_ERROR, 'message', '/path/to/file', 111, array());
		} catch (ErrorException $error) {}

		$this->assertInstanceOf('ErrorException', $error);
		$this->assertSame(E_USER_ERROR, $error->getSeverity(), 'error handler should not modify severity');
		$this->assertSame('message', $error->getMessage(), 'error handler should not modify message');
		$this->assertSame('/path/to/file', $error->getFile(), 'error handler should not modify path');
		$this->assertSame(111, $error->getLine(), 'error handler should not modify line number');
		$this->assertSame(0, $error->getCode(), 'error handler should use 0 exception code');
	}


	public function testHandleErrorOptionalArguments()
	{
		$error = null;
		try {
			$this->handler->handleError(E_USER_ERROR, 'message');
		} catch (ErrorException $error) {}

		$this->assertInstanceOf('ErrorException', $error);
		$this->assertSame('', $error->getFile(), 'error handler should use correct default path');
		$this->assertSame(0, $error->getLine(), 'error handler should use correct default line');
	}
}
