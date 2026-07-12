<?php

namespace Illuminate\Tests\Foundation\Exceptions;

use Exception;
use Illuminate\Foundation\ViteException;
use PHPUnit\Framework\TestCase;

class ViteExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new ViteException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new ViteException('Vite error.', 42, $previous);

        $this->assertSame('Vite error.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
