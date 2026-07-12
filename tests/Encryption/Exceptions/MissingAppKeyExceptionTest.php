<?php

namespace Illuminate\Tests\Encryption\Exceptions;

use Illuminate\Encryption\MissingAppKeyException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MissingAppKeyExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new MissingAppKeyException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionDefaultsToStandardMessage(): void
    {
        $exception = new MissingAppKeyException;

        $this->assertSame('No application encryption key has been specified.', $exception->getMessage());
    }

    public function testExceptionAcceptsCustomMessage(): void
    {
        $exception = new MissingAppKeyException('Custom message.');

        $this->assertSame('Custom message.', $exception->getMessage());
    }
}
