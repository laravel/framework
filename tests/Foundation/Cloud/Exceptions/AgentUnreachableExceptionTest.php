<?php

namespace Illuminate\Tests\Foundation\Cloud\Exceptions;

use Exception;
use Illuminate\Foundation\Cloud\AgentUnreachableException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AgentUnreachableExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new AgentUnreachableException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new AgentUnreachableException('Agent unreachable.', 42, $previous);

        $this->assertSame('Agent unreachable.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
