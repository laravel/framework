<?php

namespace Illuminate\Tests\Cache\Exceptions;

use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use PHPUnit\Framework\TestCase;

class LockTimeoutExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new LockTimeoutException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new LockTimeoutException('Lock timed out.', 42, $previous);

        $this->assertSame('Lock timed out.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
