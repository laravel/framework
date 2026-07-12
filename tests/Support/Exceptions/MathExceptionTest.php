<?php

namespace Illuminate\Tests\Support\Exceptions;

use Exception;
use Illuminate\Support\Exceptions\MathException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MathExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new MathException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new MathException('Division by zero.', 42, $previous);

        $this->assertSame('Division by zero.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
