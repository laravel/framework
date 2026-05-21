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

    public function test_it_may_have_a_minimum_number_of_properties(): void
    {
        $type = JsonSchema::object()->min(1);

        $this->assertEquals([
            'type' => 'object',
            'minProperties' => 1,
        ], $type->toArray());
    }

    public function test_it_may_have_a_maximum_number_of_properties(): void
    {
        $type = JsonSchema::object()->max(5);

        $this->assertEquals([
            'type' => 'object',
            'maxProperties' => 5,
        ], $type->toArray());
    }

    public function test_it_may_combine_min_and_max_properties(): void
    {
        $type = JsonSchema::object()->min(1)->max(5);

        $this->assertEquals([
            'type' => 'object',
            'minProperties' => 1,
            'maxProperties' => 5,
        ], $type->toArray());
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
