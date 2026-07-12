<?php

namespace Illuminate\Tests\Broadcasting\Exceptions;

use Exception;
use Illuminate\Broadcasting\BroadcastException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BroadcastExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new BroadcastException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new BroadcastException('Broadcast failed.', 42, $previous);

        $this->assertSame('Broadcast failed.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
