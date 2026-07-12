<?php

namespace Illuminate\Tests\Foundation\Exceptions;

use Exception;
use Illuminate\Foundation\MixFileNotFoundException;
use PHPUnit\Framework\TestCase;

class MixFileNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $exception = new MixFileNotFoundException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new MixFileNotFoundException('Mix file not found.', 42, $previous);

        $this->assertSame('Mix file not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
