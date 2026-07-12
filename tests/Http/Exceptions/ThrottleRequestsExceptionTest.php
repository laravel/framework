<?php

namespace Illuminate\Tests\Http\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ThrottleRequestsExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfTooManyRequestsHttpException()
    {
        $exception = new ThrottleRequestsException;

        $this->assertInstanceOf(TooManyRequestsHttpException::class, $exception);
    }

    public function testExceptionUsesStatusCode429()
    {
        $exception = new ThrottleRequestsException;

        $this->assertSame(429, $exception->getStatusCode());
    }

    public function testExceptionHoldsMessageHeadersCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new ThrottleRequestsException('Too many attempts.', $previous, ['Retry-After' => 60], 42);

        $this->assertSame('Too many attempts.', $exception->getMessage());
        $this->assertSame(['Retry-After' => 60], $exception->getHeaders());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
