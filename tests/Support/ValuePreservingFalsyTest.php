<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class ValuePreservingFalsyTest extends TestCase
{
    // Test when the value is 0 (integer)
    public function test_zero_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'balance' => 0],
            ['name' => 'John', 'balance' => 200],
        ]);

        $this->assertSame(0, $collection->value('balance', preserveFalsy: true));
    }

    // Test when the value is 0.0 (float)
    public function test_float_zero_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'balance' => 0.0],
            ['name' => 'John', 'balance' => 200.5],
        ]);

        $this->assertSame(0.0, $collection->value('balance', preserveFalsy: true));
    }

    // Test when the value is false (boolean)
    public function test_false_is_preserved()
    {
        $collection = collect([
            ['name' => 'John', 'vegetarian' => true],
            ['name' => 'Tim', 'vegetarian' => false],
        ]);

        $this->assertFalse($collection->where('name', 'Tim')->value('vegetarian', preserveFalsy: true));
    }

    // Test when the value is an empty string
    public function test_empty_string_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'status' => ''],
            ['name' => 'John', 'status' => 'active'],
        ]);

        $this->assertSame('', $collection->value('status', preserveFalsy: true));
    }

    // Test when the value is null
    public function test_null_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'age' => null],
            ['name' => 'John', 'age' => 30],
        ]);

        $this->assertNull($collection->value('age', preserveFalsy: true));
    }

    // Test when the value is an empty array
    public function test_empty_array_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'tags' => []],
            ['name' => 'John', 'tags' => ['admin']],
        ]);

        $this->assertSame([], $collection->value('tags', preserveFalsy: true));
    }

    // Test when the value is '0' (string zero)
    public function test_string_zero_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'balance' => '0'],
            ['name' => 'John', 'balance' => '100'],
        ]);

        $this->assertSame('0', $collection->value('balance', preserveFalsy: true));
    }

    // Test when a missing key is provided
    public function test_missing_key_returns_default()
    {
        $collection = collect([
            ['name' => 'Tim', 'balance' => 0],
            ['name' => 'John', 'balance' => 200],
        ]);

        $this->assertSame('default_value', $collection->value('missing_key', 'default_value', preserveFalsy: true));
    }

    // Test when a falsy value in a subsequent item is returned (e.g. first item is falsy, second is not)
    public function test_first_falsy_value_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'status' => 0],
            ['name' => 'John', 'status' => 'active'],
        ]);

        $this->assertSame(0, $collection->value('status', preserveFalsy: true));
    }

    // Test when a falsy value in a subsequent item is returned (string '0' case)
    public function test_first_item_with_string_zero_is_preserved()
    {
        $collection = collect([
            ['name' => 'Tim', 'status' => '0'],
            ['name' => 'John', 'status' => 'active'],
        ]);

        $this->assertSame('0', $collection->value('status', preserveFalsy: true));
    }
}
