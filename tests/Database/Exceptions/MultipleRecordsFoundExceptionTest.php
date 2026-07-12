<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\MultipleRecordsFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MultipleRecordsFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new MultipleRecordsFoundException(2);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsCountAndMessage(): void
    {
        $exception = new MultipleRecordsFoundException(3);

        $this->assertSame(3, $exception->count);
        $this->assertSame(3, $exception->getCount());
        $this->assertSame('3 records were found.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionHoldsCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new MultipleRecordsFoundException(3, 42, $previous);

        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
