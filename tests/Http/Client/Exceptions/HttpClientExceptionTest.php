<?php

namespace Illuminate\Tests\Http\Client\Exceptions;

use Exception;
use Illuminate\Http\Client\HttpClientException;
use PHPUnit\Framework\TestCase;

class HttpClientExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new HttpClientException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new HttpClientException('Client error.', 42, $previous);

        $this->assertSame('Client error.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
