<?php

namespace Illuminate\Tests\Support\Exceptions;

use Exception;
use Illuminate\Support\MultipleItemsFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultipleItemsFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new MultipleItemsFoundException(2);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsCountAndMessage(): void
    {
        $exception = new MultipleItemsFoundException(3);

        $this->assertSame(3, $exception->count);
        $this->assertSame(3, $exception->getCount());
        $this->assertSame('3 items were found.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionHoldsCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new MultipleItemsFoundException(3, 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
