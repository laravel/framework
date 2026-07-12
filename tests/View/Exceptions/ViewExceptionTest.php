<?php

namespace Illuminate\Tests\View\Exceptions;

use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\ViewException;
use PHPUnit\Framework\TestCase;

class ViewExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfErrorException()
    {
        $exception = new ViewException('View error.');

        $this->assertInstanceOf(ErrorException::class, $exception);
    }

    public function testExceptionHoldsMessage()
    {
        $exception = new ViewException('View error.');

        $this->assertSame('View error.', $exception->getMessage());
    }

    public function testReportReturnsFalseWithoutPreviousException()
    {
        $exception = new ViewException('View error.');

        $this->assertFalse($exception->report());
    }

    public function testReportReturnsFalseWhenPreviousExceptionIsNotReportable()
    {
        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, new Exception('previous'));

        $this->assertFalse($exception->report());
    }

    public function testReportDelegatesToReportableePreviousException()
    {
        $previous = new class extends Exception
        {
            public bool $reported = false;

            public function report()
            {
                $this->reported = true;

                return true;
            }
        };

        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, $previous);

        $this->assertTrue($exception->report());
        $this->assertTrue($previous->reported);
    }

    public function testRenderReturnsNullWithoutPreviousException()
    {
        $exception = new ViewException('View error.');

        $this->assertNull($exception->render(Request::create('/')));
    }

    public function testRenderReturnsNullWhenPreviousExceptionIsNotRenderable()
    {
        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, new Exception('previous'));

        $this->assertNull($exception->render(Request::create('/')));
    }

    public function testRenderDelegatesToRenderablePreviousException()
    {
        $previous = new class extends Exception
        {
            public function render($request)
            {
                return 'rendered: '.$request->path();
            }
        };

        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, $previous);

        $this->assertSame('rendered: foo', $exception->render(Request::create('/foo')));
    }
}
