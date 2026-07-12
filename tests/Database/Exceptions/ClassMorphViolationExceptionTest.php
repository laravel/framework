<?php

namespace Illuminate\Tests\Database\Exceptions;

use Illuminate\Database\ClassMorphViolationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ClassMorphViolationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new ClassMorphViolationException(new stdClass);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsModelAndMessage(): void
    {
        $exception = new ClassMorphViolationException(new stdClass);

        $this->assertSame(stdClass::class, $exception->model);
        $this->assertSame('No morph map defined for model [stdClass].', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
