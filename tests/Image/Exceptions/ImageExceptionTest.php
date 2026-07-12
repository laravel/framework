<?php

namespace Illuminate\Tests\Image\Exceptions;

use Exception;
use Illuminate\Image\ImageException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ImageExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new ImageException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new ImageException('Unable to process image.', 42, $previous);

        $this->assertSame('Unable to process image.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
