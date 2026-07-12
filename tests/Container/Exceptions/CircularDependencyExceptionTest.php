<?php

namespace Illuminate\Tests\Container\Exceptions;

use Exception;
use Illuminate\Contracts\Container\CircularDependencyException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyExceptionTest extends TestCase
{
    public function testExceptionImplementsContainerExceptionInterface()
    {
        $exception = new CircularDependencyException;

        $this->assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }

    public function testExceptionHoldsMessageCodeAndPrevious()
    {
        $previous = new Exception('previous');

        $exception = new CircularDependencyException('Circular dependency detected.', 42, $previous);

        $this->assertSame('Circular dependency detected.', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
