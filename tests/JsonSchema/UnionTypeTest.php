<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Serializer;
use Illuminate\JsonSchema\Types\UnionType;
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public function test_serializes_as_a_type_array(): void
    {
        $type = JsonSchema::union(['string', 'number', 'boolean']);

        $this->assertEquals([
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

        $this->assertEquals([
            'type' => ['string', 'number', 'null'],
        ], $type->toArray());
    }

    public function test_it_does_not_duplicate_null_when_a_member_is_already_null(): void
    {
        $type = JsonSchema::union(['string', 'null'])->nullable();

        $this->assertEquals([
            'type' => ['string', 'null'],
        ], $type->toArray());
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
