<?php

namespace Illuminate\Tests\Foundation\Cloud\Exceptions;

use Exception;
use Illuminate\Foundation\Cloud\ManagedQueueNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ManagedQueueNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new ManagedQueueNotFoundException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new ManagedQueueNotFoundException('Managed queue not found.', 42, $previous);

        $this->assertSame('Managed queue not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
