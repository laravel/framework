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

    public function test_it_may_set_exclusive_minimum(): void
    {
        $type = JsonSchema::number()->min(5.5, exclusive: true);

        $this->assertEquals([
            'type' => 'number',
            'exclusiveMinimum' => 5.5,
        ], $type->toArray());
    }

    public function test_it_may_set_exclusive_maximum(): void
    {
        $type = JsonSchema::number()->max(10.75, exclusive: true);

        $this->assertEquals([
            'type' => 'number',
            'exclusiveMaximum' => 10.75,
        ], $type->toArray());
    }

    public function test_exclusive_min_and_max_are_mutually_exclusive_with_inclusive(): void
    {
        $type = JsonSchema::number()->min(0.0)->max(100.0)->min(5.5, exclusive: true);

        $this->assertEquals([
            'type' => 'number',
            'exclusiveMinimum' => 5.5,
            'maximum' => 100.0,
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

    public function test_it_may_set_multiple_of_as_float(): void
    {
        $type = JsonSchema::number()->multipleOf(0.5);

        $this->assertEquals([
            'type' => 'number',
            'multipleOf' => 0.5,
        ], $type->toArray());
    }

    public function test_it_may_set_multiple_of_as_int(): void
    {
        $type = JsonSchema::number()->multipleOf(3);

        $this->assertEquals([
            'type' => 'number',
            'multipleOf' => 3,
        ], $type->toArray());
    }

    public function test_it_may_combine_multiple_of_with_min_and_max(): void
    {
        $type = JsonSchema::number()->min(0.0)->max(10.0)->multipleOf(0.25);

        $this->assertEquals([
            'type' => 'number',
            'minimum' => 0.0,
            'maximum' => 10.0,
            'multipleOf' => 0.25,
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
