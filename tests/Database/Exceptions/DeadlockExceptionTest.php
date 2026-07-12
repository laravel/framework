<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\DeadlockException;
use PDOException;
use PHPUnit\Framework\TestCase;

class DeadlockExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfPDOException(): void
    {
        $exception = new DeadlockException;

        $this->assertInstanceOf(PDOException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new DeadlockException('Deadlock found when trying to get lock.', 42, $previous);

        $this->assertSame('Deadlock found when trying to get lock.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
