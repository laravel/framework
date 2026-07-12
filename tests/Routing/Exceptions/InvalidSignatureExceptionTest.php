<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Illuminate\Routing\Exceptions\InvalidSignatureException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidSignatureExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfHttpException(): void
    {
        $exception = new InvalidSignatureException;

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    public function testExceptionUsesStatusCode403AndMessage(): void
    {
        $exception = new InvalidSignatureException;

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('Invalid signature.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
