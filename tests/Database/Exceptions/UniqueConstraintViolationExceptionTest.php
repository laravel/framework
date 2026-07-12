<?php

namespace Illuminate\Tests\Database\Exceptions;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;

class UniqueConstraintViolationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfQueryException(): void
    {
        $exception = new UniqueConstraintViolationException(
            'mysql', 'insert into users (email) values (?)', ['taylor@laravel.com'], new Exception('Duplicate entry')
        );

        $this->assertInstanceOf(QueryException::class, $exception);
    }

    public function testExceptionDefaultsIndexAndColumns(): void
    {
        $exception = new UniqueConstraintViolationException(
            'mysql', 'insert into users (email) values (?)', ['taylor@laravel.com'], new Exception('Duplicate entry')
        );

        $this->assertNull($exception->index);
        $this->assertSame([], $exception->columns);
    }

    public function testSetIndexIsFluentAndAssignsIndex(): void
    {
        $exception = new UniqueConstraintViolationException(
            'mysql', 'insert into users (email) values (?)', ['taylor@laravel.com'], new Exception('Duplicate entry')
        );

        $result = $exception->setIndex('users_email_unique');

        $this->assertSame($exception, $result);
        $this->assertSame('users_email_unique', $exception->index);
    }

    public function testSetColumnsIsFluentAndAssignsColumns(): void
    {
        $exception = new UniqueConstraintViolationException(
            'mysql', 'insert into users (email) values (?)', ['taylor@laravel.com'], new Exception('Duplicate entry')
        );

        $result = $exception->setColumns(['email']);

        $this->assertSame($exception, $result);
        $this->assertSame(['email'], $exception->columns);
    }
}
