<?php

namespace Illuminate\Tests\Queue\Exceptions;

use Illuminate\Contracts\Queue\EntityNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EntityNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfInvalidArgumentException()
    {
        $exception = new EntityNotFoundException('App\\Models\\User', 1);

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testExceptionUsesTypeAndIdInMessage()
    {
        $exception = new EntityNotFoundException('App\\Models\\User', 1);

        $this->assertSame(
            'Queueable entity [App\\Models\\User] not found for ID [1].',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionCastsIdToString()
    {
        $exception = new EntityNotFoundException('App\\Models\\User', null);

        $this->assertSame(
            'Queueable entity [App\\Models\\User] not found for ID [].',
            $exception->getMessage()
        );
    }
}
