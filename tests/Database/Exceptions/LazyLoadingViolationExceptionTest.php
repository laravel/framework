<?php

namespace Illuminate\Tests\Database\Exceptions;

use Illuminate\Database\LazyLoadingViolationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class LazyLoadingViolationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new LazyLoadingViolationException(new stdClass, 'posts');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testExceptionHoldsModelRelationAndMessage(): void
    {
        $exception = new LazyLoadingViolationException(new stdClass, 'posts');

        $this->assertSame(stdClass::class, $exception->model);
        $this->assertSame('posts', $exception->relation);
        $this->assertSame(
            'Attempted to lazy load [posts] on model [stdClass] but lazy loading is disabled.',
            $exception->getMessage()
        );
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}
