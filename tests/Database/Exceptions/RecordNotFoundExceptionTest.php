<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\RecordNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RecordNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new RecordNotFoundException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new RecordNotFoundException('No record found.', 42, $previous);

        $this->assertSame('No record found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
