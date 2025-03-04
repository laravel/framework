<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        // Ensure conversion
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertInstanceOf(Collection::class, $collection['nested']['deepNested']);

        // Ensure data integrity
        $this->assertEquals('value', $collection['nested']['key']);
        $this->assertEquals('deepValue', $collection['nested']['deepNested']['deepKey']);
    }

    public function testDoesNotAlterNonArrayValues()
    {
        $this->assertEquals(42, deepCollect(42));
        $this->assertEquals('Laravel', deepCollect('Laravel'));
        $this->assertNull(deepCollect(null));
    }

    public function testConvertsEmptyArraysToEmptyCollections()
    {
        $collection = deepCollect([]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    public function testHandlesDeeplyNestedArrays()
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

        $this->assertInstanceOf(Collection::class, $collection['level1']['level2']['level3']['level4']);
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

        $this->assertSame($original, $collection);
        $this->assertInstanceOf(Collection::class, $collection['nested']);
        $this->assertEquals('value', $collection['nested']['key']);
    }

    public function testConvertsQueryBuilderArrayResultsToCollections()
    {
        // Simulating a Query Builder result
        $queryResult = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $collection = deepCollect($queryResult);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertInstanceOf(Collection::class, $collection->first());
        $this->assertEquals('Alice', $collection->first()['name']);
    }

    public function testHandlesEloquentRelationResults()
    {
        // Simulating an Eloquent relation returning an array
        $model = new class extends Model {
            public function comments(): HasMany
            {
                return $this->hasMany(Comment::class);
            }
        };

        // Fake relationship array result (e.g., when calling $model->comments()->get()->toArray())
        $commentsArray = [
            ['id' => 1, 'text' => 'First comment'],
            ['id' => 2, 'text' => 'Second comment'],
        ];

        $commentsCollection = deepCollect($commentsArray);

        $this->assertInstanceOf(Collection::class, $commentsCollection);
        $this->assertInstanceOf(Collection::class, $commentsCollection->first());
        $this->assertEquals('First comment', $commentsCollection->first()['text']);
    }
}
