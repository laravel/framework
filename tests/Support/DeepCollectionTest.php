<?php

namespace Tests\Support;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DeepCollectionTest extends TestCase
{
    /** @test */
    public function it_converts_nested_arrays_into_collections()
    {
        $array = [
            'id' => 1,
            'title' => 'Sample News',
            'nested' => [
                'key' => 'value',
                'deep_nested' => [
                    'deep_key' => 'deep_value',
                ],
            ],
        ];

        $collection = deepCollect($array);

        // Assert that the top-level is a Collection
        $this->assertInstanceOf(Collection::class, $collection);

        // Assert that the nested array is also converted to a Collection
        $this->assertInstanceOf(Collection::class, $collection['nested']);

        // Assert that the deep nested array is also a Collection
        $this->assertInstanceOf(Collection::class, $collection['nested']['deep_nested']);

        // Assert that values remain unchanged
        $this->assertEquals('value', $collection['nested']['key']);
        $this->assertEquals('deep_value', $collection['nested']['deep_nested']['deep_key']);
    }

    /** @test */
    public function it_handles_non_array_values_correctly()
    {
        $this->assertEquals(123, deepCollect(123));
        $this->assertEquals('string', deepCollect('string'));
        $this->assertEquals(null, deepCollect(null));
    }

    /** @test */
    public function it_handles_empty_arrays()
    {
        $collection = deepCollect([]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEmpty($collection);
    }

    /** @test */
    public function it_handles_mixed_data_types_in_arrays()
    {
        $array = [
            'number' => 123,
            'string' => 'hello',
            'boolean' => true,
            'nested' => [
                'array' => [1, 2, 3],
                'assoc' => ['key' => 'value'],
            ],
        ];

        $collection = deepCollect($array);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertInstanceOf(Collection::class, $collection['nested']['assoc']);

        $this->assertEquals(123, $collection['number']);
        $this->assertEquals('hello', $collection['string']);
        $this->assertTrue($collection['boolean']);
        $this->assertEquals([1, 2, 3], $collection['nested']['array']->toArray());
    }
}
