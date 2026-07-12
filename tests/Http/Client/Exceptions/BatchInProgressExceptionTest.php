<?php

namespace Illuminate\Tests\Http\Client\Exceptions;

use Illuminate\Http\Client\BatchInProgressException;
use Illuminate\Http\Client\HttpClientException;
use PHPUnit\Framework\TestCase;

class BatchInProgressExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfHttpClientException(): void
    {
        $exception = new BatchInProgressException;

        $this->assertInstanceOf(HttpClientException::class, $exception);
    }

    public function testExceptionUsesFixedMessage(): void
    {
        $exception = new BatchInProgressException;

        $this->assertSame(
            'You cannot add requests to a batch that is already in progress.',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
