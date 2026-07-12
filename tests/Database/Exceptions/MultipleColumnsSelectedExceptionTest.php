<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\MultipleColumnsSelectedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultipleColumnsSelectedExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new MultipleColumnsSelectedException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new MultipleColumnsSelectedException('Multiple columns selected.', 42, $previous);

        $this->assertSame('Multiple columns selected.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
