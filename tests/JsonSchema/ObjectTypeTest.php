<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use PHPUnit\Framework\TestCase;

class ObjectTypeTest extends TestCase
{
    public function test_it_may_not_have_properties(): void
    {
        $type = JsonSchema::object()->title('Payload');

        $this->assertEquals([
            'type' => 'object',
            'title' => 'Payload',
        ], $type->toArray());
    }

    public function test_it_may_be_initialized_with_a_closure_but_without_properties(): void
    {
        $type = JsonSchema::object(fn () => [])->title('Payload');

        $this->assertEquals([
            'type' => 'object',
            'title' => 'Payload',
        ], $type->toArray());
    }

    public function test_it_may_have_properties(): void
    {
        $type = JsonSchema::object([
            'age-a' => JsonSchema::integer()->min(0)->required(),
            'age-b' => JsonSchema::integer()->default(30)->max(45),
        ])->description('Root object');

        $this->assertEquals([
            'type' => 'object',
            'description' => 'Root object',
            'properties' => [
                'age-a' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'age-b' => [
                    'type' => 'integer',
                    'default' => 30,
                    'maximum' => 45,
                ],
            ],
            'required' => ['age-a'],
        ], $type->toArray());
    }

    public function test_it_may_be_initialized_with_a_closure_but_may_have_properties(): void
    {
        $type = JsonSchema::object(fn (JsonSchemaTypeFactory $schema) => [
            'age-a' => $schema->integer()->min(0)->required(),
            'age-b' => $schema->integer()->default(30)->max(45),
        ])->description('Root object');

        $this->assertEquals([
            'type' => 'object',
            'description' => 'Root object',
            'properties' => [
                'age-a' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'age-b' => [
                    'type' => 'integer',
                    'default' => 30,
                    'maximum' => 45,
                ],
            ],
            'required' => ['age-a'],
        ], $type->toArray());
    }

    public function test_numeric_string_property_names_remain_strings_in_required_array(): void
    {
        $type = JsonSchema::object([
            '1' => JsonSchema::string()->required(),
            '4' => JsonSchema::string()->required(),
        ]);

        $array = $type->toArray();

        $this->assertSame(['1', '4'], $array['required']);
        $this->assertIsString($array['required'][0]);
        $this->assertIsString($array['required'][1]);
    }

    public function test_it_may_disable_additional_properties(): void
    {
        $type = JsonSchema::object()->default(['age' => 1])->withoutAdditionalProperties();

        $this->assertEquals([
            'type' => 'object',
            'default' => ['age' => 1],
            'additionalProperties' => false,
        ], $type->toArray());
    }

    public function test_it_may_set_min_properties(): void
    {
        $type = JsonSchema::object()->title('Payload')->min(1);

        $this->assertEquals([
            'type' => 'object',
            'title' => 'Payload',
            'minProperties' => 1,
        ], $type->toArray());
    }

    public function test_it_may_set_max_properties(): void
    {
        $type = JsonSchema::object()->description('A record')->max(5);

        $this->assertEquals([
            'type' => 'object',
            'description' => 'A record',
            'maxProperties' => 5,
        ], $type->toArray());
    }

    public function test_it_may_combine_min_and_max_properties(): void
    {
        $type = JsonSchema::object([
            'name' => JsonSchema::string()->required(),
        ])->min(1)->max(3);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['name'],
            'minProperties' => 1,
            'maxProperties' => 3,
        ], $type->toArray());
    }

    public function test_it_may_set_enum(): void
    {
        $type = JsonSchema::object()->enum([
            ['a' => 1],
            ['a' => 2],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'enum' => [
                ['a' => 1],
                ['a' => 2],
            ],
        ], $type->toArray());
    }
}
