<?php

namespace Illuminate\Tests\Console\Exceptions;

use Exception;
use Illuminate\Console\PromptValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PromptValidationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException()
    {
        $exception = new PromptValidationException;

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new PromptValidationException('The provided value is invalid.', 42, $previous);

        $this->assertSame('The provided value is invalid.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
