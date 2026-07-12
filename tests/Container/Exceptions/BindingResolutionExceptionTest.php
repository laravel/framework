<?php

namespace Illuminate\Tests\Container\Exceptions;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

class BindingResolutionExceptionTest extends TestCase
{
    public function testExceptionImplementsContainerExceptionInterface()
    {
        $exception = new BindingResolutionException;

        $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new BindingResolutionException('Target is not instantiable.', 42, $previous);

        $this->assertSame('Target is not instantiable.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
