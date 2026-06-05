<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Serializer;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DeserializerTest extends TestCase
{
    public function test_it_round_trips_a_type_built_with_the_factory(): void
    {
        $type = JsonSchema::object([
            'name' => JsonSchema::string()->min(1)->max(50)->pattern('^[a-z]+$')->required(),
            'age' => JsonSchema::integer()->min(0)->max(120)->default(18),
            'score' => JsonSchema::number()->min(0)->max(100)->multipleOf(0.5),
            'active' => JsonSchema::boolean()->default(true),
            'tags' => JsonSchema::array()->items(JsonSchema::string()->max(20))->min(1)->max(5)->unique(),
            'meta' => JsonSchema::object([
                'created' => JsonSchema::string()->format('date-time')->required(),
            ])->withoutAdditionalProperties(),
            'status' => JsonSchema::string()->enum(['draft', 'published'])->nullable(),
        ])->title('User')->description('A user payload');

        $array = Serializer::serialize($type);

        $rebuilt = JsonSchema::fromArray($array);

        $this->assertInstanceOf(ObjectType::class, $rebuilt);
        $this->assertSame($array, Serializer::serialize($rebuilt));
        $this->assertEquals($type, $rebuilt);
    }

    public function test_it_maps_every_supported_type(): void
    {
        $this->assertInstanceOf(ObjectType::class, JsonSchema::fromArray(['type' => 'object']));
        $this->assertInstanceOf(ArrayType::class, JsonSchema::fromArray(['type' => 'array']));
        $this->assertInstanceOf(StringType::class, JsonSchema::fromArray(['type' => 'string']));
        $this->assertInstanceOf(IntegerType::class, JsonSchema::fromArray(['type' => 'integer']));
        $this->assertInstanceOf(NumberType::class, JsonSchema::fromArray(['type' => 'number']));
        $this->assertInstanceOf(BooleanType::class, JsonSchema::fromArray(['type' => 'boolean']));
    }

    public function test_it_applies_string_constraints(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'string',
            'minLength' => 2,
            'maxLength' => 8,
            'pattern' => '^foo.*$',
            'format' => 'email',
        ]);

        $this->assertEquals([
            'type' => 'string',
            'minLength' => 2,
            'maxLength' => 8,
            'pattern' => '^foo.*$',
            'format' => 'email',
        ], $type->toArray());
    }

    public function test_it_applies_integer_constraints(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'integer',
            'minimum' => 0,
            'maximum' => 100,
            'multipleOf' => 5,
        ]);

        $this->assertInstanceOf(IntegerType::class, $type);
        $this->assertEquals([
            'type' => 'integer',
            'minimum' => 0,
            'maximum' => 100,
            'multipleOf' => 5,
        ], $type->toArray());
    }

    public function test_it_applies_exclusive_integer_constraints(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'integer',
            'exclusiveMinimum' => 0,
            'exclusiveMaximum' => 100,
        ]);

        $this->assertInstanceOf(IntegerType::class, $type);
        $this->assertEquals([
            'type' => 'integer',
            'exclusiveMinimum' => 0,
            'exclusiveMaximum' => 100,
        ], $type->toArray());
    }

    public function test_it_applies_number_constraints_and_preserves_floats(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'number',
            'minimum' => 0.5,
            'maximum' => 9.9,
            'multipleOf' => 0.1,
        ]);

        $this->assertInstanceOf(NumberType::class, $type);

        $array = $type->toArray();

        $this->assertSame(0.5, $array['minimum']);
        $this->assertSame(9.9, $array['maximum']);
        $this->assertSame(0.1, $array['multipleOf']);
    }

    public function test_it_applies_exclusive_number_constraints_and_preserves_floats(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'number',
            'exclusiveMinimum' => 0.5,
            'exclusiveMaximum' => 9.9,
        ]);

        $this->assertInstanceOf(NumberType::class, $type);

        $array = $type->toArray();

        $this->assertSame(0.5, $array['exclusiveMinimum']);
        $this->assertSame(9.9, $array['exclusiveMaximum']);
    }

    public function test_it_applies_array_constraints_and_nested_items(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'array',
            'items' => ['type' => 'string', 'maxLength' => 3],
            'minItems' => 1,
            'maxItems' => 4,
            'uniqueItems' => true,
        ]);

        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertEquals([
            'type' => 'array',
            'minItems' => 1,
            'maxItems' => 4,
            'items' => [
                'type' => 'string',
                'maxLength' => 3,
            ],
            'uniqueItems' => true,
        ], $type->toArray());
    }

    public function test_it_builds_nested_objects_and_marks_required_children(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1],
                'age' => ['type' => 'integer', 'minimum' => 0],
                'address' => [
                    'type' => 'object',
                    'properties' => [
                        'city' => ['type' => 'string'],
                    ],
                    'required' => ['city'],
                ],
            ],
            'required' => ['name'],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1],
                'age' => ['type' => 'integer', 'minimum' => 0],
                'address' => [
                    'type' => 'object',
                    'properties' => [
                        'city' => ['type' => 'string'],
                    ],
                    'required' => ['city'],
                ],
            ],
            'required' => ['name'],
        ], $type->toArray());
    }

    public function test_it_preserves_numeric_string_property_names_when_marking_required(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                '1' => ['type' => 'string'],
                '4' => ['type' => 'string'],
            ],
            'required' => ['1', '4'],
        ]);

        $array = $type->toArray();

        $this->assertEquals(['1', '4'], $array['required']);
        $this->assertIsString($array['required'][0]);
    }

    public function test_it_disallows_additional_properties_when_false(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'additionalProperties' => false,
        ]);

        $this->assertEquals([
            'type' => 'object',
            'additionalProperties' => false,
        ], $type->toArray());
    }

    public function test_it_normalizes_nullable_from_a_type_array(): void
    {
        $type = JsonSchema::fromArray([
            'type' => ['string', 'null'],
            'minLength' => 1,
        ]);

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals([
            'type' => ['string', 'null'],
            'minLength' => 1,
        ], $type->toArray());
    }

    public function test_it_normalizes_nullable_from_an_any_of_null_branch(): void
    {
        $type = JsonSchema::fromArray([
            'title' => 'Nickname',
            'anyOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'null'],
            ],
        ]);

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals([
            'title' => 'Nickname',
            'minLength' => 1,
            'type' => ['string', 'null'],
        ], $type->toArray());
    }

    public function test_it_normalizes_nullable_from_a_one_of_null_branch(): void
    {
        $type = JsonSchema::fromArray([
            'oneOf' => [
                ['type' => 'null'],
                ['type' => 'integer', 'minimum' => 0],
            ],
        ]);

        $this->assertInstanceOf(IntegerType::class, $type);
        $this->assertEquals([
            'minimum' => 0,
            'type' => ['integer', 'null'],
        ], $type->toArray());
    }

    public function test_it_resolves_a_local_ref_against_defs(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'author' => ['$ref' => '#/$defs/User'],
            ],
            'required' => ['author'],
            '$defs' => [
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                    'required' => ['name'],
                ],
            ],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'author' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                    'required' => ['name'],
                ],
            ],
            'required' => ['author'],
        ], $type->toArray());
    }

    public function test_it_resolves_a_local_ref_against_definitions(): void
    {
        $type = JsonSchema::fromArray([
            '$ref' => '#/definitions/Tag',
            'definitions' => [
                'Tag' => ['type' => 'string', 'minLength' => 1],
            ],
        ]);

        $this->assertInstanceOf(StringType::class, $type);
        $this->assertEquals([
            'type' => 'string',
            'minLength' => 1,
        ], $type->toArray());
    }

    public function test_it_merges_sibling_keys_over_a_ref(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'handle' => [
                    '$ref' => '#/$defs/Name',
                    'description' => 'Overridden description',
                ],
            ],
            '$defs' => [
                'Name' => [
                    'type' => 'string',
                    'description' => 'Original description',
                    'minLength' => 1,
                ],
            ],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'handle' => [
                    'description' => 'Overridden description',
                    'minLength' => 1,
                    'type' => 'string',
                ],
            ],
        ], $type->toArray());
    }

    public function test_it_throws_for_an_unresolvable_ref(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve JSON Schema $ref [#/$defs/Missing].');

        JsonSchema::fromArray([
            '$ref' => '#/$defs/Missing',
            '$defs' => [],
        ]);
    }

    public function test_it_throws_for_a_remote_ref(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve non-local JSON Schema $ref [https://example.com/user.json].');

        JsonSchema::fromArray([
            '$ref' => 'https://example.com/user.json',
        ]);
    }

    public function test_it_infers_object_type_from_properties(): void
    {
        $type = JsonSchema::fromArray([
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ]);

        $this->assertInstanceOf(ObjectType::class, $type);
    }

    public function test_it_infers_number_type_from_exclusive_boundaries(): void
    {
        $type = JsonSchema::fromArray([
            'exclusiveMinimum' => 0.5,
        ]);

        $this->assertInstanceOf(NumberType::class, $type);
    }

    public function test_it_infers_array_type_from_items(): void
    {
        $type = JsonSchema::fromArray([
            'items' => ['type' => 'integer'],
        ]);

        $this->assertInstanceOf(ArrayType::class, $type);
        $this->assertEquals([
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ], $type->toArray());
    }

    public function test_it_infers_scalar_type_from_a_homogeneous_enum(): void
    {
        $this->assertInstanceOf(StringType::class, JsonSchema::fromArray([
            'enum' => ['draft', 'published'],
        ]));

        $this->assertInstanceOf(IntegerType::class, JsonSchema::fromArray([
            'enum' => [1, 2, 3],
        ]));

        $this->assertInstanceOf(NumberType::class, JsonSchema::fromArray([
            'enum' => [1, 2.5, 3],
        ]));

        $this->assertInstanceOf(BooleanType::class, JsonSchema::fromArray([
            'enum' => [true, false],
        ]));
    }

    public function test_it_applies_enum_and_default(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'string',
            'enum' => ['draft', 'published'],
            'default' => 'draft',
        ]);

        $this->assertEquals([
            'type' => 'string',
            'default' => 'draft',
            'enum' => ['draft', 'published'],
        ], $type->toArray());
    }

    public function test_it_ignores_unknown_keywords(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'string',
            'minLength' => 1,
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$comment' => 'ignore me',
            'readOnly' => true,
            'contentEncoding' => 'base64',
        ]);

        $this->assertEquals([
            'type' => 'string',
            'minLength' => 1,
        ], $type->toArray());
    }

    public function test_it_throws_when_the_type_cannot_be_determined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine the JSON Schema type for the given schema.');

        JsonSchema::fromArray([
            'title' => 'Mystery',
        ]);
    }

    public function test_it_detects_a_circular_ref_instead_of_recursing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular JSON Schema $ref [#/$defs/node] detected.');

        JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'children' => ['type' => 'array', 'items' => ['$ref' => '#/$defs/node']],
            ],
            '$defs' => [
                'node' => [
                    'type' => 'object',
                    'properties' => [
                        'children' => ['type' => 'array', 'items' => ['$ref' => '#/$defs/node']],
                    ],
                ],
            ],
        ]);
    }

    public function test_it_resolves_the_same_ref_used_in_sibling_positions(): void
    {
        $type = JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'home' => ['$ref' => '#/$defs/address'],
                'work' => ['$ref' => '#/$defs/address'],
            ],
            '$defs' => [
                'address' => ['type' => 'object', 'properties' => ['city' => ['type' => 'string']]],
            ],
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'home' => ['type' => 'object', 'properties' => ['city' => ['type' => 'string']]],
                'work' => ['type' => 'object', 'properties' => ['city' => ['type' => 'string']]],
            ],
        ], $type->toArray());
    }

    public function test_it_throws_for_a_multi_type_union(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to represent a multi-type JSON Schema union [string, integer].');

        JsonSchema::fromArray([
            'type' => ['string', 'integer'],
        ]);
    }

    public function test_it_throws_for_a_boolean_property_schema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to represent the schema for property [meta]; boolean schemas are not supported.');

        JsonSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'meta' => true,
            ],
        ]);
    }

    public function test_it_throws_for_a_non_numeric_numeric_constraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The JSON Schema [minimum] constraint must be a number.');

        JsonSchema::fromArray([
            'type' => 'number',
            'minimum' => 'oops',
        ]);
    }

    public function test_it_throws_for_a_non_integer_integer_constraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The JSON Schema integer constraint [1.9] must be an integer.');

        JsonSchema::fromArray([
            'type' => 'integer',
            'minimum' => 1.9,
        ]);
    }

    public function test_it_throws_for_tuple_items(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tuple and boolean JSON Schema "items" are not supported.');

        JsonSchema::fromArray([
            'type' => 'array',
            'items' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ]);
    }

    public function test_it_throws_when_a_union_branch_conflicts_with_sibling_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Conflicting [type] between a "anyOf" branch and its sibling keys.');

        JsonSchema::fromArray([
            'type' => 'integer',
            'anyOf' => [
                ['type' => 'string', 'minLength' => 3],
                ['type' => 'null'],
            ],
        ]);
    }

    public function test_it_throws_for_an_unsupported_union(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only a nullable "anyOf" (a single schema plus a "null" branch) is supported.');

        JsonSchema::fromArray([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ]);
    }

    public function test_it_throws_for_a_null_default(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A null JSON Schema [default] is not supported.');

        JsonSchema::fromArray([
            'type' => 'string',
            'default' => null,
        ]);
    }

    public function test_it_resolves_the_root_ref_pointer(): void
    {
        // "#" resolves to the root, so a self-reference is detected as circular...
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Circular JSON Schema $ref [#] detected.');

        JsonSchema::fromArray(['$ref' => '#']);
    }
}
