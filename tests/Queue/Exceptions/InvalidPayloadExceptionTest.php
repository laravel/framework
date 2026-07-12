<?php

namespace Illuminate\Tests\Queue\Exceptions;

use Illuminate\Queue\InvalidPayloadException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvalidPayloadExceptionTest extends TestCase
{
    public function testExceptionIsInstanceOfInvalidArgumentException()
    {
        $exception = new InvalidPayloadException('Invalid payload.');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    public function testExceptionHoldsProvidedMessageAndValue()
    {
        $exception = new InvalidPayloadException('Invalid payload.', 'bad-json');

        $this->assertSame('Invalid payload.', $exception->getMessage());
        $this->assertSame('bad-json', $exception->value);
    }

    public function testExceptionFallsBackToJsonLastErrorWhenNoMessageGiven()
    {
        json_decode('{invalid');

        $exception = new InvalidPayloadException(null, 'bad-json');

        $this->assertSame((string) json_last_error(), $exception->getMessage());
        $this->assertSame('bad-json', $exception->value);
    }
}
