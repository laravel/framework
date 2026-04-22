<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class IntegerTypeTest extends TestCase
{
    public function test_it_may_set_min_value(): void
    {
        $type = JsonSchema::integer()->title('Age')->min(5);

        $this->assertEquals([
            'type' => 'integer',
            'title' => 'Age',
            'minimum' => 5,
        ], $type->toArray());
    }

    public function test_it_may_set_max_value(): void
    {
        $type = JsonSchema::integer()->description('Max age')->max(10);

        $this->assertEquals([
            'type' => 'integer',
            'description' => 'Max age',
            'maximum' => 10,
        ], $type->toArray());
    }

    public function test_it_may_set_default_value(): void
    {
        $type = JsonSchema::integer()->default(18);

        $this->assertEquals([
            'type' => 'integer',
            'default' => 18,
        ], $type->toArray());
    }

    public function test_it_may_set_multiple_of(): void
    {
        $type = JsonSchema::integer()->multipleOf(5);

        $this->assertEquals([
            'type' => 'integer',
            'multipleOf' => 5,
        ], $type->toArray());
    }

    public function test_it_may_combine_multiple_of_with_min_and_max(): void
    {
        $type = JsonSchema::integer()->min(0)->max(100)->multipleOf(10);

        $this->assertEquals([
            'type' => 'integer',
            'minimum' => 0,
            'maximum' => 100,
            'multipleOf' => 10,
        ], $type->toArray());
    }

    public function test_it_may_set_enum(): void
    {
        $type = JsonSchema::integer()->enum([1, 2, 3]);

        $this->assertEquals([
            'type' => 'integer',
            'enum' => [1, 2, 3],
        ], $type->toArray());
    }
}
