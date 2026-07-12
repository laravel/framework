<?php

namespace Illuminate\Tests\Http\Exceptions;

use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HttpResponseExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new HttpResponseException(new Response);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsResponse()
    {
        $response = new Response('body', 404);

        $exception = new HttpResponseException($response);

        $this->assertSame($response, $exception->getResponse());
    }

    public function testExceptionDefaultsToEmptyMessageAndCodeWithoutPrevious()
    {
        $exception = new HttpResponseException(new Response);

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionInheritsMessageAndCodeFromPrevious()
    {
        $previous = new Exception('Something went wrong.', 42);

        $exception = new HttpResponseException(new Response, $previous);

        $this->assertSame('Something went wrong.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
