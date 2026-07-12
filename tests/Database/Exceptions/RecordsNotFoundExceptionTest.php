<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\RecordsNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RecordsNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new RecordsNotFoundException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new RecordsNotFoundException('No records found.', 42, $previous);

        $this->assertSame('No records found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
