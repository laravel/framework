<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use PHPUnit\Framework\TestCase;

class JsonSchemaTypeFactoryTest extends TestCase
{
    public function test_it_creates_object_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->object();

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    public function test_it_creates_object_type_with_properties_array(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->object([
            'name' => $factory->string(),
        ]);

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ], $type->toArray());
    }

    public function test_it_creates_object_type_with_closure(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->object(fn (JsonSchemaTypeFactory $schema) => [
            'name' => $schema->string(),
            'age' => $schema->integer(),
        ]);

        $this->assertInstanceOf(ObjectType::class, $type);
        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ], $type->toArray());
    }

    public function test_it_creates_array_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->array();

        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertEquals(['type' => 'array'], $type->toArray());
    }

    public function test_it_creates_string_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->string();

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals(['type' => 'string'], $type->toArray());
    }

    public function test_it_creates_integer_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->integer();

        $this->assertInstanceOf(IntegerType::class, $type);
        $this->assertEquals(['type' => 'integer'], $type->toArray());
    }

    public function test_it_creates_number_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->number();

        $this->assertInstanceOf(NumberType::class, $type);
        $this->assertEquals(['type' => 'number'], $type->toArray());
    }

    public function test_it_creates_boolean_type(): void
    {
        $factory = new JsonSchemaTypeFactory;

        $type = $factory->boolean();

        $this->assertInstanceOf(BooleanType::class, $type);
        $this->assertEquals(['type' => 'boolean'], $type->toArray());
    }

    public function test_static_call_creates_object_type(): void
    {
        $type = JsonSchema::object();

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    public function test_static_call_creates_array_type(): void
    {
        $type = JsonSchema::array();

        $this->assertInstanceOf(ArrayType::class, $type);
    }

    public function test_static_call_creates_string_type(): void
    {
        $type = JsonSchema::string();

        $this->assertInstanceOf(StringType::class, $type);
    }

    public function test_static_call_creates_integer_type(): void
    {
        $type = JsonSchema::integer();

        $this->assertInstanceOf(IntegerType::class, $type);
    }

    public function test_static_call_creates_number_type(): void
    {
        $type = JsonSchema::number();

        $this->assertInstanceOf(NumberType::class, $type);
    }

    public function test_static_call_creates_boolean_type(): void
    {
        $type = JsonSchema::boolean();

        $this->assertInstanceOf(BooleanType::class, $type);
    }

    public function test_fluent_api_is_chainable(): void
    {
        $type = JsonSchema::object([
            'name' => JsonSchema::string()->title('Name')->description('User name')->min(1)->max(255)->required(),
            'email' => JsonSchema::string()->format('email')->pattern('^[a-z]+@.*$'),
            'age' => JsonSchema::integer()->min(0)->max(150)->default(18),
            'score' => JsonSchema::number()->min(0.0)->max(100.0),
            'active' => JsonSchema::boolean()->default(true),
            'tags' => JsonSchema::array()->items(JsonSchema::string())->min(0)->max(10),
        ])->title('User')->description('User schema');

        $this->assertEquals([
            'type' => 'object',
            'title' => 'User',
            'description' => 'User schema',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'Name',
                    'description' => 'User name',
                    'minLength' => 1,
                    'maxLength' => 255,
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'pattern' => '^[a-z]+@.*$',
                ],
                'age' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 150,
                    'default' => 18,
                ],
                'score' => [
                    'type' => 'number',
                    'minimum' => 0.0,
                    'maximum' => 100.0,
                ],
                'active' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'minItems' => 0,
                    'maxItems' => 10,
                ],
            ],
            'required' => ['name'],
        ], $type->toArray());
    }

    public function test_deeply_nested_objects(): void
    {
        $type = JsonSchema::object([
            'user' => JsonSchema::object([
                'profile' => JsonSchema::object([
                    'name' => JsonSchema::string()->required(),
                    'address' => JsonSchema::object([
                        'city' => JsonSchema::string(),
                        'country' => JsonSchema::string()->required(),
                    ]),
                ]),
            ]),
        ]);

        $result = $type->toArray();

        $this->assertEquals('object', $result['type']);
        $this->assertEquals('object', $result['properties']['user']['type']);
        $this->assertEquals('object', $result['properties']['user']['properties']['profile']['type']);
        $this->assertEquals('string', $result['properties']['user']['properties']['profile']['properties']['name']['type']);
        $this->assertEquals(['name'], $result['properties']['user']['properties']['profile']['required']);
        $this->assertEquals('object', $result['properties']['user']['properties']['profile']['properties']['address']['type']);
        $this->assertEquals(['country'], $result['properties']['user']['properties']['profile']['properties']['address']['required']);
    }

    public function test_array_with_object_items(): void
    {
        $type = JsonSchema::array()->items(
            JsonSchema::object([
                'id' => JsonSchema::integer()->required(),
                'name' => JsonSchema::string()->required(),
            ])
        );

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                ],
                'required' => ['id', 'name'],
            ],
        ], $type->toArray());
    }
}

