<?php

namespace Illuminate\Tests\Encryption\Exceptions;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DecryptExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new DecryptException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new DecryptException('Could not decrypt the data.', 42, $previous);

        $this->assertSame('Could not decrypt the data.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
