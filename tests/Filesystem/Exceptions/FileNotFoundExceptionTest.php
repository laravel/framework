<?php

namespace Illuminate\Tests\Filesystem\Exceptions;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use PHPUnit\Framework\TestCase;

class FileNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new FileNotFoundException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new FileNotFoundException('File not found.', 42, $previous);

        $this->assertSame('File not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
