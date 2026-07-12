<?php

namespace Illuminate\Tests\Support\Exceptions;

use Exception;
use Illuminate\Support\ItemNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ItemNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new ItemNotFoundException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new ItemNotFoundException('No item found.', 42, $previous);

        $this->assertSame('No item found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
