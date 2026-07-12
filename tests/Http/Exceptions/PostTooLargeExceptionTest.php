<?php

namespace Illuminate\Tests\Http\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\PostTooLargeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PostTooLargeExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfHttpException(): void
    {
        $exception = new PostTooLargeException;

        $this->assertInstanceOf(HttpException::class, $exception);
    }

    public function testExceptionUsesStatusCode413(): void
    {
        $exception = new PostTooLargeException;

        $this->assertSame(413, $exception->getStatusCode());
    }

    public function testExceptionHoldsMessageHeadersCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new PostTooLargeException('The POST data is too large.', $previous, ['X-Foo' => 'Bar'], 42);

        $this->assertSame('The POST data is too large.', $exception->getMessage());
        $this->assertSame(['X-Foo' => 'Bar'], $exception->getHeaders());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
