<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\Types\Type;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SerializerTest extends TestCase
{
    public function test_it_does_not_know_how_to_serialize_unknown_types(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported [Illuminate\\JsonSchema\\Types\\Type@anonymous');

        $type = new class extends Type {
            // anonymous type for triggering serializer failure
        };

        $type->toArray();
    }
}
