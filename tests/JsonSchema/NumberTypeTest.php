<?php

namespace Illuminate\Tests\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use PHPUnit\Framework\TestCase;

class NumberTypeTest extends TestCase
{
    public function test_it_may_set_min_value_as_float(): void
    {
        $type = JsonSchema::number()->title('Price')->min(5.5);

        $this->assertEquals([
            'type' => 'number',
            'title' => 'Price',
            'minimum' => 5.5,
        ], $type->toArray());
    }

    public function test_it_may_set_min_value_as_int(): void
    {
        $type = JsonSchema::number()->title('Price')->min(5);

        $this->assertEquals([
            'type' => 'number',
            'title' => 'Price',
            'minimum' => 5,
        ], $type->toArray());
    }

    public function test_it_may_set_max_value_as_float(): void
    {
        $type = JsonSchema::number()->description('Max price')->max(10.75);

        $this->assertEquals([
            'type' => 'number',
            'description' => 'Max price',
            'maximum' => 10.75,
        ], $type->toArray());
    }

    public function test_it_may_set_max_value_as_int(): void
    {
        $type = JsonSchema::number()->description('Max price')->max(10);

        $this->assertEquals([
            'type' => 'number',
            'description' => 'Max price',
            'maximum' => 10,
        ], $type->toArray());
    }

    public function test_it_may_set_default_value(): void
    {
        $type = JsonSchema::number()->default(9.99);

        $this->assertEquals([
            'type' => 'number',
            'default' => 9.99,
        ], $type->toArray());
    }

    public function test_it_may_set_enum(): void
    {
        $type = JsonSchema::number()->enum([1, 2.5, 3]);

        $this->assertEquals([
            'type' => 'number',
            'enum' => [1, 2.5, 3],
        ], $type->toArray());
    }
}
