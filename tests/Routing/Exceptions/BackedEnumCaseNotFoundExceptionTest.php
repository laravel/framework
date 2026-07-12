<?php

namespace Illuminate\Tests\Routing\Exceptions;

use Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BackedEnumCaseNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new BackedEnumCaseNotFoundException('App\\Enums\\Status', 'archived');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionUsesEnumClassAndCaseInMessage(): void
    {
        $exception = new BackedEnumCaseNotFoundException('App\\Enums\\Status', 'archived');

        $this->assertSame(
            'Case [archived] not found on Backed Enum [App\\Enums\\Status].',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
