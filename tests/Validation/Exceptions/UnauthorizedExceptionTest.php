<?php

namespace Illuminate\Tests\Validation\Exceptions;

use Exception;
use Illuminate\Validation\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UnauthorizedExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new UnauthorizedException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new UnauthorizedException('This action is unauthorized.', 42, $previous);

        $this->assertSame('This action is unauthorized.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
