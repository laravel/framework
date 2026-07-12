<?php

namespace Illuminate\Tests\Session\Exceptions;

use Exception;
use Illuminate\Session\TokenMismatchException;
use PHPUnit\Framework\TestCase;

class TokenMismatchExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new TokenMismatchException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new TokenMismatchException('CSRF token mismatch.', 42, $previous);

        $this->assertSame('CSRF token mismatch.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
