<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Routing\Exceptions\StreamedResponseException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamedResponseExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new StreamedResponseException(new Exception('Stream failed.'));

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsOriginalExceptionAndItsMessage(): void
    {
        $original = new Exception('Stream failed.');

        $exception = new StreamedResponseException($original);

        $this->assertSame($original, $exception->originalException);
        $this->assertSame($original, $exception->getInnerException());
        $this->assertSame('Stream failed.', $exception->getMessage());
    }

    public function testRenderReturnsEmptyResponse(): void
    {
        $exception = new StreamedResponseException(new Exception('Stream failed.'));

        $response = $exception->render();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('', $response->getContent());
    }
}
