<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class CompositeTypeTest extends TestCase
{
    public function testOneOf()
    {
        $type = JsonSchema::oneOf([
            JsonSchema::string(),
            JsonSchema::integer(),
        ])->title('Identifier');

        $this->assertEquals([
            'title' => 'Identifier',
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ], $type->toArray());
    }

    public function testOneOfAcceptsAClosure()
    {
        $type = JsonSchema::oneOf(fn ($schema) => [
            $schema->string(),
            $schema->integer(),
        ]);

        $this->assertEquals([
            'oneOf' => [
                ['type' => 'string'],
                ['type' => 'integer'],
            ],
        ], $type->toArray());
    }

    public function testAnyOf()
    {
        $type = JsonSchema::anyOf([
            JsonSchema::string()->min(3),
            JsonSchema::integer()->min(100),
        ]);

        $this->assertEquals([
            'anyOf' => [
                [
                    'minLength' => 3,
                    'type' => 'string',
                ],
                [
                    'minimum' => 100,
                    'type' => 'integer',
                ],
            ],
        ], $type->toArray());
    }

    public function testAllOf()
    {
        $type = JsonSchema::allOf([
            JsonSchema::string()->min(3),
            JsonSchema::string()->max(10),
        ]);

        $this->assertEquals([
            'allOf' => [
                [
                    'minLength' => 3,
                    'type' => 'string',
                ],
                [
                    'maxLength' => 10,
                    'type' => 'string',
                ],
            ],
        ], $type->toArray());
    }

    public function testConst()
    {
        $type = JsonSchema::const('active');

        $this->assertEquals([
            'const' => 'active',
        ], $type->toArray());
    }

    public function testConstOnTypedSchemas()
    {
        $type = JsonSchema::string()->const('active');

        $this->assertEquals([
            'const' => 'active',
            'type' => 'string',
        ], $type->toArray());
    }

    public function testNullConst()
    {
        $type = JsonSchema::const(null);

        $this->assertEquals([
            'const' => null,
        ], $type->toArray());
    }
}
