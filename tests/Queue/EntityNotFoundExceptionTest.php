<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Queue\EntityNotFoundException;
use PHPUnit\Framework\TestCase;

class EntityNotFoundExceptionTest extends TestCase
{
    public function test_it_creates_the_proper_exception_message()
    {
        $type = 'App\\Models\\User';
        $id = 123;

        $exception = new EntityNotFoundException($type, $id);

        $this->assertSame(
            "Queueable entity [{$type}] not found for ID [{$id}].",
            $exception->getMessage()
        );
    }

    public function test_it_converts_non_string_ids_to_strings()
    {
        $type = 'App\\Models\\Post';
        $id = 456.789;

        $exception = new EntityNotFoundException($type, $id);

        $this->assertSame(
            "Queueable entity [{$type}] not found for ID [456.789].",
            $exception->getMessage()
        );
    }

    public function test_it_extends_invalid_argument_exception()
    {
        $exception = new EntityNotFoundException('App\\Model', 1);

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}