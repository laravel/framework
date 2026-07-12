<?php

namespace Illuminate\Tests\Database\Exceptions;

use Illuminate\Database\SQLiteDatabaseDoesNotExistException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SQLiteDatabaseDoesNotExistExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfInvalidArgumentException(): void
    {
        $exception = new SQLiteDatabaseDoesNotExistException('/path/to/database.sqlite');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testExceptionHoldsPathAndMessage(): void
    {
        $exception = new SQLiteDatabaseDoesNotExistException('/path/to/database.sqlite');

        $this->assertSame('/path/to/database.sqlite', $exception->path);
        $this->assertSame(
            'Database file at path [/path/to/database.sqlite] does not exist. Ensure this is an absolute path to the database.',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
