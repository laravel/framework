<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RefTypeTest extends TestCase
{
    public function test_it_serializes_a_reference(): void
    {
        $this->assertEquals(['$ref' => '#/$defs/node'], JsonSchema::ref('node')->toArray());
    }

    public function test_it_serializes_a_reference_with_a_description(): void
    {
        $this->assertEquals([
            '$ref' => '#/$defs/node',
            'description' => 'The child node.',
        ], JsonSchema::ref('node')->description('The child node.')->toArray());
    }

    public function test_it_serializes_a_nullable_reference_as_any_of(): void
    {
        $this->assertEquals([
            'anyOf' => [
                ['$ref' => '#/$defs/node'],
                ['type' => 'null'],
            ],
        ], JsonSchema::ref('node')->nullable()->toArray());
    }

    public function test_it_escapes_the_definition_name_in_the_pointer(): void
    {
        $this->assertEquals(['$ref' => '#/$defs/a~1b~0c'], JsonSchema::ref('a/b~c')->toArray());
    }

    public function test_defined_types_are_hoisted_into_root_defs(): void
    {
        $schema = new JsonSchemaTypeFactory;

        $type = $schema->object([
            'expression' => $schema->object([
                'operation' => $schema->string()->nullable()->required(),
                'children' => $schema->array()->items($schema->ref('expression'))->nullable()->required(),
            ])->define('expression')->required(),
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'expression' => ['$ref' => '#/$defs/expression'],
            ],
            'required' => ['expression'],
            '$defs' => [
                'expression' => [
                    'type' => 'object',
                    'properties' => [
                        'operation' => ['type' => ['string', 'null']],
                        'children' => [
                            'type' => ['array', 'null'],
                            'items' => ['$ref' => '#/$defs/expression'],
                        ],
                    ],
                    'required' => ['operation', 'children'],
                ],
            ],
        ], $type->toArray());
    }

    public function test_definitions_nested_in_definitions_are_hoisted_to_the_root(): void
    {
        $schema = new JsonSchemaTypeFactory;

        $value = $schema->object([
            'constant' => $schema->number(),
        ])->define('value');

        $type = $schema->object([
            'expression' => $schema->object([
                'value' => $value,
                'children' => $schema->array()->items($schema->ref('expression')),
            ])->define('expression'),
            'fallback' => $value,
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'expression' => ['$ref' => '#/$defs/expression'],
                'fallback' => ['$ref' => '#/$defs/value'],
            ],
            '$defs' => [
                'expression' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => ['$ref' => '#/$defs/value'],
                        'children' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/$defs/expression'],
                        ],
                    ],
                ],
                'value' => [
                    'type' => 'object',
                    'properties' => [
                        'constant' => ['type' => 'number'],
                    ],
                ],
            ],
        ], $type->toArray());
    }

    public function test_a_defined_root_is_inlined_and_registered(): void
    {
        $schema = new JsonSchemaTypeFactory;

        $type = $schema->object([
            'children' => $schema->array()->items($schema->ref('node')),
        ])->define('node');

        $body = [
            'type' => 'object',
            'properties' => [
                'children' => [
                    'type' => 'array',
                    'items' => ['$ref' => '#/$defs/node'],
                ],
            ],
        ];

        $this->assertEquals([...$body, '$defs' => ['node' => $body]], $type->toArray());
    }

    public function test_definitions_inside_any_of_are_hoisted(): void
    {
        $schema = new JsonSchemaTypeFactory;

        $type = $schema->object([
            'value' => $schema->anyOf([
                $schema->string(),
                $schema->object(['next' => $schema->ref('link')])->define('link'),
            ]),
        ]);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'value' => [
                    'anyOf' => [
                        ['type' => 'string'],
                        ['$ref' => '#/$defs/link'],
                    ],
                ],
            ],
            '$defs' => [
                'link' => [
                    'type' => 'object',
                    'properties' => [
                        'next' => ['$ref' => '#/$defs/link'],
                    ],
                ],
            ],
        ], $type->toArray());
    }

    public function test_conflicting_definitions_throw(): void
    {
        $schema = new JsonSchemaTypeFactory;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The schema definition [node] has already been defined.');

        $schema->object([
            'a' => $schema->string()->define('node'),
            'b' => $schema->integer()->define('node'),
        ])->toArray();
    }
}
