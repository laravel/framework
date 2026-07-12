<?php

namespace Illuminate\Tests\Foundation\Exceptions;

use Exception;
use Illuminate\Foundation\ViteException;
use Illuminate\Foundation\ViteManifestNotFoundException;
use PHPUnit\Framework\TestCase;

class ViteManifestNotFoundExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfViteException()
    {
        $exception = new ViteManifestNotFoundException;

        $this->assertInstanceOf(ViteException::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new ViteManifestNotFoundException('Vite manifest not found.', 42, $previous);

        $this->assertSame('Vite manifest not found.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
