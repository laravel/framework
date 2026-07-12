<?php

namespace Illuminate\Tests\Console\Exceptions;

use Exception;
use Illuminate\Console\ManuallyFailedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ManuallyFailedExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new ManuallyFailedException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new ManuallyFailedException('Manually failed.', 42, $previous);

        $this->assertSame('Manually failed.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
