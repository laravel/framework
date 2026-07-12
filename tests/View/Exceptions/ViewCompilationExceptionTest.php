<?php

namespace Illuminate\Tests\View\Exceptions;

use Exception;
use Illuminate\Contracts\View\ViewCompilationException;
use PHPUnit\Framework\TestCase;

class ViewCompilationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new ViewCompilationException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new ViewCompilationException('Could not compile the view.', 42, $previous);

        $this->assertSame('Could not compile the view.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
