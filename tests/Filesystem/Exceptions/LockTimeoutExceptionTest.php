<?php

namespace Illuminate\Tests\Filesystem\Exceptions;

use Exception;
use Illuminate\Contracts\Filesystem\LockTimeoutException;
use PHPUnit\Framework\TestCase;

class LockTimeoutExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException()
    {
        $exception = new LockTimeoutException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new LockTimeoutException('Lock timed out.', 42, $previous);

        $this->assertSame('Lock timed out.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
