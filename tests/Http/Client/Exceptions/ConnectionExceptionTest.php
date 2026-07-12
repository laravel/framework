<?php

namespace Illuminate\Tests\Http\Client\Exceptions;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use PHPUnit\Framework\TestCase;

class ConnectionExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfHttpClientException(): void
    {
        $exception = new ConnectionException;

        $this->assertInstanceOf(HttpClientException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new ConnectionException('Could not connect.', 42, $previous);

        $this->assertSame('Could not connect.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
