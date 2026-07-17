<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Serializer;
use Illuminate\JsonSchema\Types\UnionType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public function test_serializes_as_a_type_array(): void
    {
        $type = JsonSchema::union(['string', 'number', 'boolean']);

        $this->assertSame([
            'type' => ['string', 'number', 'boolean'],
        ], $type->toArray());
    }

    public function test_serializes_with_metadata(): void
    {
        $type = JsonSchema::union(['string', 'number'])
            ->title('Value')
            ->description('A string or a number');

        $this->assertEquals([
            'type' => ['string', 'number'],
            'title' => 'Value',
            'description' => 'A string or a number',
        ], $type->toArray());
    }

    public function test_dedupes_and_preserves_member_order(): void
    {
        $type = JsonSchema::union(['number', 'string', 'number', 'boolean', 'string']);

        $this->assertSame(['number', 'string', 'boolean'], $type->types());
        $this->assertSame(['type' => ['number', 'string', 'boolean']], $type->toArray());
    }

    public function test_appends_null_when_nullable(): void
    {
        $type = JsonSchema::union(['string', 'number'])->nullable();

        $this->assertSame([
            'type' => ['string', 'number', 'null'],
        ], $type->toArray());
    }

    public function test_it_normalizes_a_null_member_into_nullability(): void
    {
        $type = JsonSchema::union(['string', 'number', 'null']);

        $this->assertSame(['string', 'number'], $type->types());
        $this->assertSame([
            'type' => ['string', 'number', 'null'],
        ], $type->toArray());
    }

    public function test_it_does_not_duplicate_null_when_already_nullable(): void
    {
        $type = JsonSchema::union(['string', 'null'])->nullable();

        $this->assertSame([
            'type' => ['string', 'null'],
        ], $type->toArray());
    }

    public function test_it_rejects_an_unsupported_member_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported JSON Schema type [wat] in a multi-type union.');

        JsonSchema::union(['string', 'wat']);
    }

    public function test_it_rejects_a_non_string_member(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported JSON Schema type [123] in a multi-type union.');

        JsonSchema::union(['string', 123]);
    }

    public function test_it_round_trips_a_union(): void
    {
        $schema = ['type' => ['string', 'number', 'boolean']];

        $type = JsonSchema::fromArray($schema);

        $this->assertInstanceOf(UnionType::class, $type);
        $this->assertSame($schema, Serializer::serialize($type));
        $this->assertEquals($type, JsonSchema::fromArray(Serializer::serialize($type)));
    }

    public function test_it_round_trips_a_nullable_union(): void
    {
        $schema = ['type' => ['string', 'number', 'null']];

        $type = JsonSchema::fromArray($schema);

        $this->assertInstanceOf(UnionType::class, $type);
        $this->assertSame($schema, Serializer::serialize($type));
    }
}
