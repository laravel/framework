<?php

namespace Illuminate\Tests\Encryption\Exceptions;

use Exception;
use Illuminate\Contracts\Encryption\EncryptException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EncryptExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new EncryptException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new EncryptException('Could not encrypt the data.', 42, $previous);

        $this->assertSame('Could not encrypt the data.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
