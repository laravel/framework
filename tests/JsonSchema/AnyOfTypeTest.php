<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class AnyOfTypeTest extends TestCase
{
    public function test_it_may_describe_any_of_multiple_schemas(): void
    {
        $type = JsonSchema::anyOf([
            JsonSchema::string(),
            JsonSchema::integer(),
        ])->title('Identifier');

        $this->assertSame([
            'title' => 'Identifier',
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ], $type->toArray());
    }

    public function test_it_may_be_initialized_with_a_closure(): void
    {
        $type = JsonSchema::anyOf(fn (JsonSchema $schema): array => [
            $schema->string(),
            $schema->integer(),
        ]);

        $this->assertSame([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ], $type->toArray());
    }

    public function test_it_may_be_nullable(): void
    {
        $type = JsonSchema::anyOf([
            JsonSchema::string(),
            JsonSchema::integer(),
        ])->nullable();

        $this->assertSame([
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
                ['type' => 'null'],
            ],
        ], $type->toArray());
    }

    public function test_it_may_describe_object_unions(): void
    {
        $type = JsonSchema::anyOf([
            JsonSchema::object([
                'type' => JsonSchema::string()->enum(['article'])->required(),
                'title' => JsonSchema::string()->required(),
                'content' => JsonSchema::string()->required(),
            ]),
            JsonSchema::object([
                'type' => JsonSchema::string()->enum(['image'])->required(),
                'url' => JsonSchema::string()->required(),
                'caption' => JsonSchema::string(),
            ]),
        ]);

        $this->assertEquals([
            'anyOf' => [
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['article']],
                        'title' => ['type' => 'string'],
                        'content' => ['type' => 'string'],
                    ],
                    'required' => ['type', 'title', 'content'],
                ],
                [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['image']],
                        'url' => ['type' => 'string'],
                        'caption' => ['type' => 'string'],
                    ],
                    'required' => ['type', 'url'],
                ],
            ],
        ], $type->toArray());
    }
}
