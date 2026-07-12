<?php

namespace Illuminate\Tests\Container\Exceptions;

use Exception;
use Illuminate\Container\EntryNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundExceptionTest extends TestCase
{
    public function testExceptionImplementsNotFoundExceptionInterface()
    {
        $exception = new EntryNotFoundException;

        $this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new EntryNotFoundException('Entry not found.', 42, $previous);

        $this->assertSame('Entry not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
