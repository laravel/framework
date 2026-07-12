<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\LostConnectionException;
use LogicException;
use PHPUnit\Framework\TestCase;

class LostConnectionExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfLogicException(): void
    {
        $exception = new LostConnectionException;

        $this->assertInstanceOf(LogicException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new LostConnectionException('Lost connection to the database.', 42, $previous);

        $this->assertSame('Lost connection to the database.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
