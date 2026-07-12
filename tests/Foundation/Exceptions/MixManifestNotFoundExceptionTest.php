<?php

namespace Illuminate\Tests\Foundation\Exceptions;

use Exception;
use Illuminate\Foundation\MixManifestNotFoundException;
use PHPUnit\Framework\TestCase;

class MixManifestNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfException(): void
    {
        $exception = new MixManifestNotFoundException;

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious(): void
    {
        $previous = new Exception('previous');

        $exception = new MixManifestNotFoundException('Mix manifest not found.', 42, $previous);

        $this->assertSame('Mix manifest not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
