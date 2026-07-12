<?php

namespace Illuminate\Tests\Cache\Exceptions;

use Exception;
use Illuminate\Cache\Limiters\LimiterTimeoutException;
use PHPUnit\Framework\TestCase;

class LimiterTimeoutExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $exception = new LimiterTimeoutException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new LimiterTimeoutException('Limiter timed out.', 42, $previous);

        $this->assertSame('Limiter timed out.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
