<?php

namespace Illuminate\Tests\Http\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\OriginMismatchException;
use PHPUnit\Framework\TestCase;

class OriginMismatchExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $exception = new OriginMismatchException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new OriginMismatchException('The request origin does not match.', 42, $previous);

        $this->assertSame('The request origin does not match.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
