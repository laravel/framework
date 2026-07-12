<?php

namespace Illuminate\Tests\View\Exceptions;

use ErrorException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\View\ViewException;
use PHPUnit\Framework\TestCase;

class ViewExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfErrorException(): void
    {
        $exception = new ViewException('View error.');

        $this->assertInstanceOf(ErrorException::class, $exception);
    }

    public function testExceptionHoldsMessage(): void
    {
        $exception = new ViewException('View error.');

        $this->assertSame('View error.', $exception->getMessage());
    }

    public function testReportReturnsFalseWithoutPreviousException(): void
    {
        $exception = new ViewException('View error.');

        $this->assertFalse($exception->report());
    }

    public function testReportReturnsFalseWhenPreviousExceptionIsNotReportable(): void
    {
        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, new Exception('previous'));

        $this->assertFalse($exception->report());
    }

    public function testReportDelegatesToReportableePreviousException(): void
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

    public function testRenderReturnsNullWithoutPreviousException(): void
    {
        $exception = new ViewException('View error.');

        $this->assertNull($exception->render(Request::create('/')));
    }

    public function testRenderReturnsNullWhenPreviousExceptionIsNotRenderable(): void
    {
        $exception = new ViewException('View error.', 0, 1, __FILE__, __LINE__, new Exception('previous'));

        $this->assertNull($exception->render(Request::create('/')));
    }

    public function testRenderDelegatesToRenderablePreviousException(): void
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
