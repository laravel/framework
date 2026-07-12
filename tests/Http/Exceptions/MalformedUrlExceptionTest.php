<?php

namespace Illuminate\Tests\Http\Exceptions;

use Illuminate\Http\Exceptions\MalformedUrlException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MalformedUrlExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfHttpException()
    {
        $exception = new MalformedUrlException;

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    public function testExceptionUsesStatusCode400AndMessage()
    {
        $exception = new MalformedUrlException;

        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame('Malformed URL.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
