<?php

namespace Illuminate\Tests\Http\Resources\JsonApi\Exceptions;

use Illuminate\Http\Resources\JsonApi\Exceptions\ResourceIdentificationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ResourceIdentificationExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = ResourceIdentificationException::attemptingToDetermineIdFor(new stdClass);

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testAttemptingToDetermineIdForObject(): void
    {
        $exception = ResourceIdentificationException::attemptingToDetermineIdFor(new stdClass);

        $this->assertSame('Unable to resolve resource object ID for [stdClass].', $exception->getMessage());
    }

    public function testAttemptingToDetermineIdForScalar(): void
    {
        $exception = ResourceIdentificationException::attemptingToDetermineIdFor('foo');

        $this->assertSame('Unable to resolve resource object ID for [string].', $exception->getMessage());
    }

    public function testAttemptingToDetermineTypeForObject(): void
    {
        $exception = ResourceIdentificationException::attemptingToDetermineTypeFor(new stdClass);

        $this->assertSame('Unable to resolve resource object type for [stdClass].', $exception->getMessage());
    }

    public function testAttemptingToDetermineTypeForScalar(): void
    {
        $exception = ResourceIdentificationException::attemptingToDetermineTypeFor(42);

        $this->assertSame('Unable to resolve resource object type for [integer].', $exception->getMessage());
    }
}
