<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class ArrayTypeTest extends TestCase
{
    public function test_it_may_set_min_items(): void
    {
        $type = JsonSchema::array()->title('Tags')->min(1);

        $this->assertEquals([
            'type' => 'array',
            'title' => 'Tags',
            'minItems' => 1,
        ], $type->toArray());
    }

    public function test_it_may_set_max_items(): void
    {
        $type = JsonSchema::array()->description('A list of tags')->max(10);

        $this->assertEquals([
            'type' => 'array',
            'description' => 'A list of tags',
            'maxItems' => 10,
        ], $type->toArray());
    }

    public function test_it_may_set_items_type(): void
    {
        $type = JsonSchema::array()->items(
            JsonSchema::string()->max(20)
        );

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => 'string',
                'maxLength' => 20,
            ],
        ], $type->toArray());
    }

    public function test_it_may_set_default_value(): void
    {
        $type = JsonSchema::array()->default(['a', 'b']);

        $this->assertEquals([
            'type' => 'array',
            'default' => ['a', 'b'],
        ], $type->toArray());
    }

    public function test_it_may_set_enum(): void
    {
        $type = JsonSchema::array()->enum([
            ['a'],
            ['b', 'c'],
        ]);

        $this->assertEquals([
            'type' => 'array',
            'enum' => [
                ['a'],
                ['b', 'c'],
            ],
        ], $type->toArray());
    }
}
