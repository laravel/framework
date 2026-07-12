<?php

namespace Illuminate\Tests\Http\Client\Exceptions;

use Illuminate\Http\Client\StrayRequestException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StrayRequestExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new StrayRequestException('http://foo.com');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionUsesUriInMessage()
    {
        $exception = new StrayRequestException('http://foo.com');

        $this->assertSame(
            'Attempted request to [http://foo.com] without a matching fake.',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
