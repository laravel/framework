<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class DeepCollectionTest extends TestCase
{
    public function testRecursivelyConvertsNestedArraysToCollections()
    {
        $array = [
            'id' => 1,
            'title' => 'Sample',
            'nested' => [
                'key' => 'value',
                'deepNested' => [
                    'deepKey' => 'deepValue',
                ],
            ],
        ];

        $collection = deepCollect($array);

        // Ensure the top-level is a Collection
        $this->assertInstanceOf(Collection::class, $collection);

        // Ensure nested arrays are converted
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertInstanceOf(Collection::class, $collection['nested']['deepNested']);

        // Ensure values remain the same
        $this->assertEquals('value', $collection['nested']['key']);
        $this->assertEquals('deepValue', $collection['nested']['deepNested']['deepKey']);
    }

    public function testReturnsNonArrayValuesUnchanged()
    {
        $this->assertEquals(42, deepCollect(42));
        $this->assertEquals('Laravel', deepCollect('Laravel'));
        $this->assertEquals(null, deepCollect(null));
    }

    public function testConvertsEmptyArraysToEmptyCollections()
    {
        $collection = deepCollect([]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    public function testTransformsMixedNestedStructuresIntoCollections()
    {
        $array = [
            'number' => 100,
            'boolean' => false,
            'nested' => [
                'list' => [10, 20, 30],
                'assoc' => ['key' => 'value'],
            ],
        ];

        $collection = deepCollect($array);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertInstanceOf(Collection::class, $collection['nested']['assoc']);

        // Verify values remain unchanged
        $this->assertEquals(100, $collection['number']);
        $this->assertFalse($collection['boolean']);
        $this->assertEquals([10, 20, 30], $collection['nested']['list']->toArray());
    }

    public function testHandlesDeeplyNestedArraysCorrectly()
    {
        $array = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => [
                            'key' => 'finalValue',
                        ],
                    ],
                ],
            ],
        ];

        $collection = deepCollect($array);

        // Assert each level is converted
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Collection::class, $collection['level1']);
        $this->assertInstanceOf(Collection::class, $collection['level1']['level2']);
        $this->assertInstanceOf(Collection::class, $collection['level1']['level2']['level3']);
        $this->assertInstanceOf(Collection::class, $collection['level1']['level2']['level3']['level4']);

        // Assert value remains correct
        $this->assertEquals('finalValue', $collection['level1']['level2']['level3']['level4']['key']);
    }

    public function testPreservesObjectsWithoutConversion()
    {
        $object = (object) ['name' => 'Laravel'];
        $array = ['framework' => $object];

        $collection = deepCollect($array);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame($object, $collection['framework']);
    }

    public function testDoesNotReconvertExistingCollections()
    {
        $original = collect([
            'name' => 'Laravel',
            'nested' => collect(['key' => 'value']),
        ]);

        $collection = deepCollect($original);

        // Ensure the structure remains untouched
        $this->assertSame($original, $collection);
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertEquals('value', $collection['nested']['key']);
    }
}
