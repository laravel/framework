<?php

namespace Illuminate\Tests\Support;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use CachingIterator;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\MultipleItemsFoundException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Symfony\Component\VarDumper\VarDumper;
use Traversable;
use UnexpectedValueException;

if (PHP_VERSION_ID >= 80100) {
    include_once 'Enums.php';
}

class SupportCollectionTest extends TestCase
{
    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstReturnsFirstItemInCollection($collection)
    {
        $c = new $collection(['foo', 'bar']);
        $this->assertSame('foo', $c->first());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstWithCallback($collection)
    {
        $data = new $collection(['foo', 'bar', 'baz']);
        $result = $data->first(function ($value) {
            return $value === 'bar';
        });
        $this->assertSame('bar', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstWithCallbackAndDefault($collection)
    {
        $data = new $collection(['foo', 'bar']);
        $result = $data->first(function ($value) {
            return $value === 'baz';
        }, 'default');
        $this->assertSame('default', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstWithDefaultAndWithoutCallback($collection)
    {
        $data = new $collection;
        $result = $data->first(null, 'default');
        $this->assertSame('default', $result);

        $data = new $collection(['foo', 'bar']);
        $result = $data->first(null, 'default');
        $this->assertSame('foo', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleReturnsFirstItemInCollectionIfOnlyOneExists($collection)
    {
        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $this->assertSame(['name' => 'foo'], $collection->where('name', 'foo')->sole());
        $this->assertSame(['name' => 'foo'], $collection->sole('name', '=', 'foo'));
        $this->assertSame(['name' => 'foo'], $collection->sole('name', 'foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleThrowsExceptionIfNoItemsExist($collection)
    {
        $this->expectException(ItemNotFoundException::class);

        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $collection->where('name', 'INVALID')->sole();
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleThrowsExceptionIfMoreThanOneItemExists($collection)
    {
        $this->expectExceptionObject(new MultipleItemsFoundException(2));

        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $collection->where('name', 'foo')->sole();
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleReturnsFirstItemInCollectionIfOnlyOneExistsWithCallback($collection)
    {
        $data = new $collection(['foo', 'bar', 'baz']);
        $result = $data->sole(function ($value) {
            return $value === 'bar';
        });
        $this->assertSame('bar', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleThrowsExceptionIfNoItemsExistWithCallback($collection)
    {
        $this->expectException(ItemNotFoundException::class);

        $data = new $collection(['foo', 'bar', 'baz']);

        $data->sole(function ($value) {
            return $value === 'invalid';
        });
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSoleThrowsExceptionIfMoreThanOneItemExistsWithCallback($collection)
    {
        $this->expectExceptionObject(new MultipleItemsFoundException(2));

        $data = new $collection(['foo', 'bar', 'bar']);

        $data->sole(function ($value) {
            return $value === 'bar';
        });
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailReturnsFirstItemInCollection($collection)
    {
        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $this->assertSame(['name' => 'foo'], $collection->where('name', 'foo')->firstOrFail());
        $this->assertSame(['name' => 'foo'], $collection->firstOrFail('name', '=', 'foo'));
        $this->assertSame(['name' => 'foo'], $collection->firstOrFail('name', 'foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailThrowsExceptionIfNoItemsExist($collection)
    {
        $this->expectException(ItemNotFoundException::class);

        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $collection->where('name', 'INVALID')->firstOrFail();
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailDoesntThrowExceptionIfMoreThanOneItemExists($collection)
    {
        $collection = new $collection([
            ['name' => 'foo'],
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]);

        $this->assertSame(['name' => 'foo'], $collection->where('name', 'foo')->firstOrFail());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailReturnsFirstItemInCollectionIfOnlyOneExistsWithCallback($collection)
    {
        $data = new $collection(['foo', 'bar', 'baz']);
        $result = $data->firstOrFail(function ($value) {
            return $value === 'bar';
        });
        $this->assertSame('bar', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailThrowsExceptionIfNoItemsExistWithCallback($collection)
    {
        $this->expectException(ItemNotFoundException::class);

        $data = new $collection(['foo', 'bar', 'baz']);

        $data->firstOrFail(function ($value) {
            return $value === 'invalid';
        });
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailDoesntThrowExceptionIfMoreThanOneItemExistsWithCallback($collection)
    {
        $data = new $collection(['foo', 'bar', 'bar']);

        $this->assertSame(
            'bar',
            $data->firstOrFail(function ($value) {
                return $value === 'bar';
            })
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstOrFailStopsIteratingAtFirstMatch($collection)
    {
        $data = new $collection([
            function () {
                return false;
            },
            function () {
                return true;
            },
            function () {
                throw new Exception();
            },
        ]);

        $this->assertNotNull($data->firstOrFail(function ($callback) {
            return $callback();
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFirstWhere($collection)
    {
        $data = new $collection([
            ['material' => 'paper', 'type' => 'book'],
            ['material' => 'rubber', 'type' => 'gasket'],
        ]);

        $this->assertSame('book', $data->firstWhere('material', 'paper')['type']);
        $this->assertSame('gasket', $data->firstWhere('material', 'rubber')['type']);
        $this->assertNull($data->firstWhere('material', 'nonexistent'));
        $this->assertNull($data->firstWhere('nonexistent', 'key'));

        $this->assertSame('book', $data->firstWhere(fn ($value) => $value['material'] === 'paper')['type']);
        $this->assertSame('gasket', $data->firstWhere(fn ($value) => $value['material'] === 'rubber')['type']);
        $this->assertNull($data->firstWhere(fn ($value) => $value['material'] === 'nonexistent'));
        $this->assertNull($data->firstWhere(fn ($value) => ($value['nonexistent'] ?? null) === 'key'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testLastReturnsLastItemInCollection($collection)
    {
        $c = new $collection(['foo', 'bar']);
        $this->assertSame('bar', $c->last());

        $c = new $collection([]);
        $this->assertNull($c->last());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testLastWithCallback($collection)
    {
        $data = new $collection([100, 200, 300]);
        $result = $data->last(function ($value) {
            return $value < 250;
        });
        $this->assertEquals(200, $result);

        $result = $data->last(function ($value, $key) {
            return $key < 2;
        });
        $this->assertEquals(200, $result);

        $result = $data->last(function ($value) {
            return $value > 300;
        });
        $this->assertNull($result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testLastWithCallbackAndDefault($collection)
    {
        $data = new $collection(['foo', 'bar']);
        $result = $data->last(function ($value) {
            return $value === 'baz';
        }, 'default');
        $this->assertSame('default', $result);

        $data = new $collection(['foo', 'bar', 'Bar']);
        $result = $data->last(function ($value) {
            return $value === 'bar';
        }, 'default');
        $this->assertSame('bar', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testLastWithDefaultAndWithoutCallback($collection)
    {
        $data = new $collection;
        $result = $data->last(null, 'default');
        $this->assertSame('default', $result);
    }

    public function testPopReturnsAndRemovesLastItemInCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertSame('bar', $c->pop());
        $this->assertSame('foo', $c->first());
    }

    public function testPopReturnsAndRemovesLastXItemsInCollection()
    {
        $c = new Collection(['foo', 'bar', 'baz']);

        $this->assertEquals(new Collection(['baz', 'bar']), $c->pop(2));
        $this->assertSame('foo', $c->first());

        $this->assertEquals(new Collection(['baz', 'bar', 'foo']), (new Collection(['foo', 'bar', 'baz']))->pop(6));
    }

    public function testShiftReturnsAndRemovesFirstItemInCollection()
    {
        $data = new Collection(['Taylor', 'Otwell']);

        $this->assertSame('Taylor', $data->shift());
        $this->assertSame('Otwell', $data->first());
        $this->assertSame('Otwell', $data->shift());
        $this->assertNull($data->first());
    }

    public function testShiftReturnsAndRemovesFirstXItemsInCollection()
    {
        $data = new Collection(['foo', 'bar', 'baz']);

        $this->assertEquals(new Collection(['foo', 'bar']), $data->shift(2));
        $this->assertSame('baz', $data->first());

        $this->assertEquals(new Collection(['foo', 'bar', 'baz']), (new Collection(['foo', 'bar', 'baz']))->shift(6));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliding($collection)
    {
        // Default parameters: $size = 2, $step = 1
        $this->assertSame([], $collection::times(0)->sliding()->toArray());
        $this->assertSame([], $collection::times(1)->sliding()->toArray());
        $this->assertSame([[1, 2]], $collection::times(2)->sliding()->toArray());
        $this->assertSame(
            [[1, 2], [2, 3]],
            $collection::times(3)->sliding()->map->values()->toArray()
        );

        // Custom step: $size = 2, $step = 3
        $this->assertSame([], $collection::times(1)->sliding(2, 3)->toArray());
        $this->assertSame([[1, 2]], $collection::times(2)->sliding(2, 3)->toArray());
        $this->assertSame([[1, 2]], $collection::times(3)->sliding(2, 3)->toArray());
        $this->assertSame([[1, 2]], $collection::times(4)->sliding(2, 3)->toArray());
        $this->assertSame(
            [[1, 2], [4, 5]],
            $collection::times(5)->sliding(2, 3)->map->values()->toArray()
        );

        // Custom size: $size = 3, $step = 1
        $this->assertSame([], $collection::times(2)->sliding(3)->toArray());
        $this->assertSame([[1, 2, 3]], $collection::times(3)->sliding(3)->toArray());
        $this->assertSame(
            [[1, 2, 3], [2, 3, 4]],
            $collection::times(4)->sliding(3)->map->values()->toArray()
        );
        $this->assertSame(
            [[1, 2, 3], [2, 3, 4]],
            $collection::times(4)->sliding(3)->map->values()->toArray()
        );

        // Custom size and custom step: $size = 3, $step = 2
        $this->assertSame([], $collection::times(2)->sliding(3, 2)->toArray());
        $this->assertSame([[1, 2, 3]], $collection::times(3)->sliding(3, 2)->toArray());
        $this->assertSame([[1, 2, 3]], $collection::times(4)->sliding(3, 2)->toArray());
        $this->assertSame(
            [[1, 2, 3], [3, 4, 5]],
            $collection::times(5)->sliding(3, 2)->map->values()->toArray()
        );
        $this->assertSame(
            [[1, 2, 3], [3, 4, 5]],
            $collection::times(6)->sliding(3, 2)->map->values()->toArray()
        );

        // Ensure keys are preserved, and inner chunks are also collections
        $chunks = $collection::times(3)->sliding();

        $this->assertSame([[0 => 1, 1 => 2], [1 => 2, 2 => 3]], $chunks->toArray());

        $this->assertInstanceOf($collection, $chunks);
        $this->assertInstanceOf($collection, $chunks->first());
        $this->assertInstanceOf($collection, $chunks->skip(1)->first());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEmptyCollectionIsEmpty($collection)
    {
        $c = new $collection;

        $this->assertTrue($c->isEmpty());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEmptyCollectionIsNotEmpty($collection)
    {
        $c = new $collection(['foo', 'bar']);

        $this->assertFalse($c->isEmpty());
        $this->assertTrue($c->isNotEmpty());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollectionIsConstructed($collection)
    {
        $data = new $collection('foo');
        $this->assertSame(['foo'], $data->all());

        $data = new $collection(2);
        $this->assertSame([2], $data->all());

        $data = new $collection(false);
        $this->assertSame([false], $data->all());

        $data = new $collection(null);
        $this->assertEmpty($data->all());

        $data = new $collection;
        $this->assertEmpty($data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollectionShuffleWithSeed($collection)
    {
        $data = new $collection(range(0, 100, 10));

        $firstRandom = $data->shuffle(1234);
        $secondRandom = $data->shuffle(1234);

        $this->assertEquals($firstRandom, $secondRandom);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSkipMethod($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6]);

        // Total items to skip is smaller than collection length
        $this->assertSame([5, 6], $data->skip(4)->values()->all());

        // Total items to skip is more than collection length
        $this->assertSame([], $data->skip(10)->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSkipUntil($collection)
    {
        $data = new $collection([1, 1, 2, 2, 3, 3, 4, 4]);

        // Item at the beginning of the collection
        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->skipUntil(1)->values()->all());

        // Item at the middle of the collection
        $this->assertSame([3, 3, 4, 4], $data->skipUntil(3)->values()->all());

        // Item not in the collection
        $this->assertSame([], $data->skipUntil(5)->values()->all());

        // Item at the beginning of the collection
        $data = $data->skipUntil(function ($value, $key) {
            return $value <= 1;
        })->values();

        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->all());

        // Item at the middle of the collection
        $data = $data->skipUntil(function ($value, $key) {
            return $value >= 3;
        })->values();

        $this->assertSame([3, 3, 4, 4], $data->all());

        // Item not in the collection
        $data = $data->skipUntil(function ($value, $key) {
            return $value >= 5;
        })->values();

        $this->assertSame([], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSkipWhile($collection)
    {
        $data = new $collection([1, 1, 2, 2, 3, 3, 4, 4]);

        // Item at the beginning of the collection
        $this->assertSame([2, 2, 3, 3, 4, 4], $data->skipWhile(1)->values()->all());

        // Item not in the collection
        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->skipWhile(5)->values()->all());

        // Item in the collection but not at the beginning
        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->skipWhile(2)->values()->all());

        // Item not in the collection
        $data = $data->skipWhile(function ($value, $key) {
            return $value >= 5;
        })->values();

        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->all());

        // Item in the collection but not at the beginning
        $data = $data->skipWhile(function ($value, $key) {
            return $value >= 2;
        })->values();

        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], $data->all());

        // Item at the beginning of the collection
        $data = $data->skipWhile(function ($value, $key) {
            return $value < 3;
        })->values();

        $this->assertSame([3, 3, 4, 4], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGetArrayableItems($collection)
    {
        $data = new $collection;

        $class = new ReflectionClass($collection);
        $method = $class->getMethod('getArrayableItems');
        $method->setAccessible(true);

        $items = new TestArrayableObject;
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new TestJsonableObject;
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new TestJsonSerializeObject;
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = new TestJsonSerializeWithScalarValueObject;
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo'], $array);

        $subject = [new stdClass, new stdClass];
        $items = new TestTraversableAndJsonSerializableObject($subject);
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame($subject, $array);

        $items = new $collection(['foo' => 'bar']);
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);

        $items = ['foo' => 'bar'];
        $array = $method->invokeArgs($data, [$items]);
        $this->assertSame(['foo' => 'bar'], $array);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testToArrayCallsToArrayOnEachItemInCollection($collection)
    {
        $item1 = m::mock(Arrayable::class);
        $item1->shouldReceive('toArray')->once()->andReturn('foo.array');
        $item2 = m::mock(Arrayable::class);
        $item2->shouldReceive('toArray')->once()->andReturn('bar.array');
        $c = new $collection([$item1, $item2]);
        $results = $c->toArray();

        $this->assertEquals(['foo.array', 'bar.array'], $results);
    }

    public function testLazyReturnsLazyCollection()
    {
        $data = new Collection([1, 2, 3, 4, 5]);

        $lazy = $data->lazy();

        $data->add(6);

        $this->assertInstanceOf(LazyCollection::class, $lazy);
        $this->assertSame([1, 2, 3, 4, 5], $lazy->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testJsonSerializeCallsToArrayOrJsonSerializeOnEachItemInCollection($collection)
    {
        $item1 = m::mock(JsonSerializable::class);
        $item1->shouldReceive('jsonSerialize')->once()->andReturn('foo.json');
        $item2 = m::mock(Arrayable::class);
        $item2->shouldReceive('toArray')->once()->andReturn('bar.array');
        $c = new $collection([$item1, $item2]);
        $results = $c->jsonSerialize();

        $this->assertEquals(['foo.json', 'bar.array'], $results);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testToJsonEncodesTheJsonSerializeResult($collection)
    {
        $c = $this->getMockBuilder($collection)->onlyMethods(['jsonSerialize'])->getMock();
        $c->expects($this->once())->method('jsonSerialize')->willReturn(['foo']);
        $results = $c->toJson();
        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), $results);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCastingToStringJsonEncodesTheToArrayResult($collection)
    {
        $c = $this->getMockBuilder($collection)->onlyMethods(['jsonSerialize'])->getMock();
        $c->expects($this->once())->method('jsonSerialize')->willReturn(['foo']);

        $this->assertJsonStringEqualsJsonString(json_encode(['foo']), (string) $c);
    }

    public function testOffsetAccess()
    {
        $c = new Collection(['name' => 'taylor']);
        $this->assertSame('taylor', $c['name']);
        $c['name'] = 'dayle';
        $this->assertSame('dayle', $c['name']);
        $this->assertTrue(isset($c['name']));
        unset($c['name']);
        $this->assertFalse(isset($c['name']));
        $c[] = 'jason';
        $this->assertSame('jason', $c[0]);
    }

    public function testArrayAccessOffsetExists()
    {
        $c = new Collection(['foo', 'bar', null]);
        $this->assertTrue($c->offsetExists(0));
        $this->assertTrue($c->offsetExists(1));
        $this->assertFalse($c->offsetExists(2));
    }

    public function testBehavesLikeAnArrayWithArrayAccess()
    {
        // indexed array
        $input = ['foo', null];
        $c = new Collection($input);
        $this->assertEquals(isset($input[0]), isset($c[0])); // existing value
        $this->assertEquals(isset($input[1]), isset($c[1])); // existing but null value
        $this->assertEquals(isset($input[1000]), isset($c[1000])); // non-existing value
        $this->assertEquals($input[0], $c[0]);
        $this->assertEquals($input[1], $c[1]);

        // associative array
        $input = ['k1' => 'foo', 'k2' => null];
        $c = new Collection($input);
        $this->assertEquals(isset($input['k1']), isset($c['k1'])); // existing value
        $this->assertEquals(isset($input['k2']), isset($c['k2'])); // existing but null value
        $this->assertEquals(isset($input['k3']), isset($c['k3'])); // non-existing value
        $this->assertEquals($input['k1'], $c['k1']);
        $this->assertEquals($input['k2'], $c['k2']);
    }

    public function testArrayAccessOffsetGet()
    {
        $c = new Collection(['foo', 'bar']);
        $this->assertSame('foo', $c->offsetGet(0));
        $this->assertSame('bar', $c->offsetGet(1));
    }

    public function testArrayAccessOffsetSet()
    {
        $c = new Collection(['foo', 'foo']);

        $c->offsetSet(1, 'bar');
        $this->assertSame('bar', $c[1]);

        $c->offsetSet(null, 'qux');
        $this->assertSame('qux', $c[2]);
    }

    public function testArrayAccessOffsetUnset()
    {
        $c = new Collection(['foo', 'bar']);

        $c->offsetUnset(1);
        $this->assertFalse(isset($c[1]));
    }

    public function testForgetSingleKey()
    {
        $c = new Collection(['foo', 'bar']);
        $c = $c->forget(0)->all();
        $this->assertFalse(isset($c['foo']));
        $this->assertFalse(isset($c[0]));
        $this->assertTrue(isset($c[1]));

        $c = new Collection(['foo' => 'bar', 'baz' => 'qux']);
        $c = $c->forget('foo')->all();
        $this->assertFalse(isset($c['foo']));
        $this->assertTrue(isset($c['baz']));
    }

    public function testForgetArrayOfKeys()
    {
        $c = new Collection(['foo', 'bar', 'baz']);
        $c = $c->forget([0, 2])->all();
        $this->assertFalse(isset($c[0]));
        $this->assertFalse(isset($c[2]));
        $this->assertTrue(isset($c[1]));

        $c = new Collection(['name' => 'taylor', 'foo' => 'bar', 'baz' => 'qux']);
        $c = $c->forget(['foo', 'baz'])->all();
        $this->assertFalse(isset($c['foo']));
        $this->assertFalse(isset($c['baz']));
        $this->assertTrue(isset($c['name']));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCountable($collection)
    {
        $c = new $collection(['foo', 'bar']);
        $this->assertCount(2, $c);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCountByStandalone($collection)
    {
        $c = new $collection(['foo', 'foo', 'foo', 'bar', 'bar', 'foobar']);
        $this->assertEquals(['foo' => 3, 'bar' => 2, 'foobar' => 1], $c->countBy()->all());

        $c = new $collection([true, true, false, false, false]);
        $this->assertEquals([true => 2, false => 3], $c->countBy()->all());

        $c = new $collection([1, 5, 1, 5, 5, 1]);
        $this->assertEquals([1 => 3, 5 => 3], $c->countBy()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCountByWithKey($collection)
    {
        $c = new $collection([
            ['key' => 'a'], ['key' => 'a'], ['key' => 'a'], ['key' => 'a'],
            ['key' => 'b'], ['key' => 'b'], ['key' => 'b'],
        ]);
        $this->assertEquals(['a' => 4, 'b' => 3], $c->countBy('key')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCountableByWithCallback($collection)
    {
        $c = new $collection(['alice', 'aaron', 'bob', 'carla']);
        $this->assertEquals(['a' => 2, 'b' => 1, 'c' => 1], $c->countBy(function ($name) {
            return substr($name, 0, 1);
        })->all());

        $c = new $collection([1, 2, 3, 4, 5]);
        $this->assertEquals([true => 2, false => 3], $c->countBy(function ($i) {
            return $i % 2 === 0;
        })->all());
    }

    public function testAdd()
    {
        $c = new Collection([]);
        $this->assertEquals([1], $c->add(1)->values()->all());
        $this->assertEquals([1, 2], $c->add(2)->values()->all());
        $this->assertEquals([1, 2, ''], $c->add('')->values()->all());
        $this->assertEquals([1, 2, '', null], $c->add(null)->values()->all());
        $this->assertEquals([1, 2, '', null, false], $c->add(false)->values()->all());
        $this->assertEquals([1, 2, '', null, false, []], $c->add([])->values()->all());
        $this->assertEquals([1, 2, '', null, false, [], 'name'], $c->add('name')->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testContainsOneItem($collection)
    {
        $this->assertFalse((new $collection([]))->containsOneItem());
        $this->assertTrue((new $collection([1]))->containsOneItem());
        $this->assertFalse((new $collection([1, 2]))->containsOneItem());
    }

    public function testIterable()
    {
        $c = new Collection(['foo']);
        $this->assertInstanceOf(ArrayIterator::class, $c->getIterator());
        $this->assertEquals(['foo'], $c->getIterator()->getArrayCopy());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCachingIterator($collection)
    {
        $c = new $collection(['foo']);
        $this->assertInstanceOf(CachingIterator::class, $c->getCachingIterator());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFilter($collection)
    {
        $c = new $collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([1 => ['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->all());

        $c = new $collection(['', 'Hello', '', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->filter()->values()->toArray());

        $c = new $collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
        $this->assertEquals(['first' => 'Hello', 'second' => 'World'], $c->filter(function ($item, $key) {
            return $key !== 'id';
        })->all());

        $c = new $collection([1, 2, 3, null, false, '', 0, []]);
        $this->assertEquals([1, 2, 3], $c->filter()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderKeyBy($collection)
    {
        $c = new $collection([
            ['id' => 'id1', 'name' => 'first'],
            ['id' => 'id2', 'name' => 'second'],
        ]);

        $this->assertEquals(['id1' => 'first', 'id2' => 'second'], $c->keyBy->id->map->name->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderUnique($collection)
    {
        $c = new $collection([
            ['id' => '1', 'name' => 'first'],
            ['id' => '1', 'name' => 'second'],
        ]);

        $this->assertCount(1, $c->unique->id);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderFilter($collection)
    {
        $c = new $collection([
            new class
            {
                public $name = 'Alex';

                public function active()
                {
                    return true;
                }
            },
            new class
            {
                public $name = 'John';

                public function active()
                {
                    return false;
                }
            },
        ]);

        $this->assertCount(1, $c->filter->active());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhere($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', '=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', '==', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where('v', 'garbage', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3]],
            $c->where('v', '===', 3)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 4]],
            $c->where('v', '<>', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 4]],
            $c->where('v', '!=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => '3'], ['v' => 4]],
            $c->where('v', '!==', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3']],
            $c->where('v', '<=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->where('v', '>=', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 1], ['v' => 2]],
            $c->where('v', '<', 3)->values()->all()
        );
        $this->assertEquals(
            [['v' => 4]],
            $c->where('v', '>', 3)->values()->all()
        );

        $object = (object) ['foo' => 'bar'];

        $this->assertEquals(
            [],
            $c->where('v', $object)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->where('v', '<>', $object)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->where('v', '!=', $object)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->where('v', '!==', $object)->values()->all()
        );

        $this->assertEquals(
            [],
            $c->where('v', '>', $object)->values()->all()
        );

        $this->assertEquals(
            [['v' => 3], ['v' => '3']],
            $c->where(fn ($value) => $value['v'] == 3)->values()->all()
        );

        $this->assertEquals(
            [['v' => 3]],
            $c->where(fn ($value) => $value['v'] === 3)->values()->all()
        );

        $c = new $collection([['v' => 1], ['v' => $object]]);
        $this->assertEquals(
            [['v' => $object]],
            $c->where('v', $object)->values()->all()
        );

        $this->assertEquals(
            [['v' => 1], ['v' => $object]],
            $c->where('v', '<>', null)->values()->all()
        );

        $this->assertEquals(
            [],
            $c->where('v', '<', null)->values()->all()
        );

        $c = new $collection([['v' => 1], ['v' => new HtmlString('hello')]]);
        $this->assertEquals(
            [['v' => new HtmlString('hello')]],
            $c->where('v', 'hello')->values()->all()
        );

        $c = new $collection([['v' => 1], ['v' => 'hello']]);
        $this->assertEquals(
            [['v' => 'hello']],
            $c->where('v', new HtmlString('hello'))->values()->all()
        );

        $c = new $collection([['v' => 1], ['v' => 2], ['v' => null]]);
        $this->assertEquals(
            [['v' => 1], ['v' => 2]],
            $c->where('v')->values()->all()
        );

        $c = new $collection([
            ['v' => 1, 'g' => 3],
            ['v' => 2, 'g' => 2],
            ['v' => 2, 'g' => 3],
            ['v' => 2, 'g' => null],
        ]);
        $this->assertEquals([['v' => 2, 'g' => 3]], $c->where('v', 2)->where('g', 3)->values()->all());
        $this->assertEquals([['v' => 2, 'g' => 3]], $c->where('v', 2)->where('g', '>', 2)->values()->all());
        $this->assertEquals([], $c->where('v', 2)->where('g', 4)->values()->all());
        $this->assertEquals([['v' => 2, 'g' => null]], $c->where('v', 2)->whereNull('g')->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereStrict($collection)
    {
        $c = new $collection([['v' => 3], ['v' => '3']]);

        $this->assertEquals(
            [['v' => 3]],
            $c->whereStrict('v', 3)->values()->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereInstanceOf($collection)
    {
        $c = new $collection([new stdClass, new stdClass, new $collection, new stdClass, new Str]);
        $this->assertCount(3, $c->whereInstanceOf(stdClass::class));

        $this->assertCount(4, $c->whereInstanceOf([stdClass::class, Str::class]));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereIn($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3], ['v' => '3']], $c->whereIn('v', [1, 3])->values()->all());
        $this->assertEquals([], $c->whereIn('v', [2])->whereIn('v', [1, 3])->values()->all());
        $this->assertEquals([['v' => 1]], $c->whereIn('v', [1])->whereIn('v', [1, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereInStrict($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 1], ['v' => 3]], $c->whereInStrict('v', [1, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNotIn($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 2], ['v' => 4]], $c->whereNotIn('v', [1, 3])->values()->all());
        $this->assertEquals([['v' => 4]], $c->whereNotIn('v', [2])->whereNotIn('v', [1, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNotInStrict($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);
        $this->assertEquals([['v' => 2], ['v' => '3'], ['v' => 4]], $c->whereNotInStrict('v', [1, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testValues($collection)
    {
        $c = new $collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);
        $this->assertEquals([['id' => 2, 'name' => 'World']], $c->filter(function ($item) {
            return $item['id'] == 2;
        })->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testValuesResetKey($collection)
    {
        $data = new $collection([1 => 'a', 2 => 'b', 3 => 'c']);
        $this->assertEquals([0 => 'a', 1 => 'b', 2 => 'c'], $data->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testValue($collection)
    {
        $c = new $collection([['id' => 1, 'name' => 'Hello'], ['id' => 2, 'name' => 'World']]);

        $this->assertEquals('Hello', $c->value('name'));
        $this->assertEquals('World', $c->where('id', 2)->value('name'));

        $c = new $collection([
            ['id' => 1, 'pivot' => ['value' => 'foo']],
            ['id' => 2, 'pivot' => ['value' => 'bar']],
        ]);

        $this->assertEquals(['value' => 'foo'], $c->value('pivot'));
        $this->assertEquals('foo', $c->value('pivot.value'));
        $this->assertEquals('bar', $c->where('id', 2)->value('pivot.value'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testBetween($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

        $this->assertEquals([['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]],
            $c->whereBetween('v', [2, 4])->values()->all());
        $this->assertEquals([['v' => 1]], $c->whereBetween('v', [-1, 1])->all());
        $this->assertEquals([['v' => 3], ['v' => '3']], $c->whereBetween('v', [3, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNotBetween($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

        $this->assertEquals([['v' => 1]], $c->whereNotBetween('v', [2, 4])->values()->all());
        $this->assertEquals([['v' => 2], ['v' => 3], ['v' => 3], ['v' => 4]], $c->whereNotBetween('v', [-1, 1])->values()->all());
        $this->assertEquals([['v' => 1], ['v' => '2'], ['v' => '4']], $c->whereNotBetween('v', [3, 3])->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFlatten($collection)
    {
        // Flat arrays are unaffected
        $c = new $collection(['#foo', '#bar', '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays are flattened with existing flat items
        $c = new $collection([['#foo', '#bar'], '#baz']);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Sets of nested arrays are flattened
        $c = new $collection([['#foo', '#bar'], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Deeply nested arrays are flattened
        $c = new $collection([['#foo', ['#bar']], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections are flattened alongside arrays
        $c = new $collection([new $collection(['#foo', '#bar']), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested collections containing plain arrays are flattened
        $c = new $collection([new $collection(['#foo', ['#bar']]), ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections are flattened
        $c = new $collection([['#foo', new $collection(['#bar'])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#baz'], $c->flatten()->all());

        // Nested arrays containing collections containing arrays are flattened
        $c = new $collection([['#foo', new $collection(['#bar', ['#zap']])], ['#baz']]);
        $this->assertEquals(['#foo', '#bar', '#zap', '#baz'], $c->flatten()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFlattenWithDepth($collection)
    {
        // No depth flattens recursively
        $c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

        // Specifying a depth only flattens to that depth
        $c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', ['#bar', ['#baz']], '#zap'], $c->flatten(1)->all());

        $c = new $collection([['#foo', ['#bar', ['#baz']]], '#zap']);
        $this->assertEquals(['#foo', '#bar', ['#baz'], '#zap'], $c->flatten(2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFlattenIgnoresKeys($collection)
    {
        // No depth ignores keys
        $c = new $collection(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten()->all());

        // Depth of 1 ignores keys
        $c = new $collection(['#foo', ['key' => '#bar'], ['key' => '#baz'], 'key' => '#zap']);
        $this->assertEquals(['#foo', '#bar', '#baz', '#zap'], $c->flatten(1)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeNull($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello'], $c->merge(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeArray($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello', 'id' => 1], $c->merge(['id' => 1])->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeCollection($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'World', 'id' => 1], $c->merge(new $collection(['name' => 'World', 'id' => 1]))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeRecursiveNull($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello'], $c->mergeRecursive(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeRecursiveArray($collection)
    {
        $c = new $collection(['name' => 'Hello', 'id' => 1]);
        $this->assertEquals(['name' => 'Hello', 'id' => [1, 2]], $c->mergeRecursive(['id' => 2])->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMergeRecursiveCollection($collection)
    {
        $c = new $collection(['name' => 'Hello', 'id' => 1, 'meta' => ['tags' => ['a', 'b'], 'roles' => 'admin']]);
        $this->assertEquals(
            ['name' => 'Hello', 'id' => 1, 'meta' => ['tags' => ['a', 'b', 'c'], 'roles' => ['admin', 'editor']]],
            $c->mergeRecursive(new $collection(['meta' => ['tags' => ['c'], 'roles' => 'editor']]))->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceNull($collection)
    {
        $c = new $collection(['a', 'b', 'c']);
        $this->assertEquals(['a', 'b', 'c'], $c->replace(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceArray($collection)
    {
        $c = new $collection(['a', 'b', 'c']);
        $this->assertEquals(['a', 'd', 'e'], $c->replace([1 => 'd', 2 => 'e'])->all());

        $c = new $collection(['a', 'b', 'c']);
        $this->assertEquals(['a', 'd', 'e', 'f', 'g'], $c->replace([1 => 'd', 2 => 'e', 3 => 'f', 4 => 'g'])->all());

        $c = new $collection(['name' => 'amir', 'family' => 'otwell']);
        $this->assertEquals(['name' => 'taylor', 'family' => 'otwell', 'age' => 26], $c->replace(['name' => 'taylor', 'age' => 26])->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceCollection($collection)
    {
        $c = new $collection(['a', 'b', 'c']);
        $this->assertEquals(
            ['a', 'd', 'e'],
            $c->replace(new $collection([1 => 'd', 2 => 'e']))->all()
        );

        $c = new $collection(['a', 'b', 'c']);
        $this->assertEquals(
            ['a', 'd', 'e', 'f', 'g'],
            $c->replace(new $collection([1 => 'd', 2 => 'e', 3 => 'f', 4 => 'g']))->all()
        );

        $c = new $collection(['name' => 'amir', 'family' => 'otwell']);
        $this->assertEquals(
            ['name' => 'taylor', 'family' => 'otwell', 'age' => 26],
            $c->replace(new $collection(['name' => 'taylor', 'age' => 26]))->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceRecursiveNull($collection)
    {
        $c = new $collection(['a', 'b', ['c', 'd']]);
        $this->assertEquals(['a', 'b', ['c', 'd']], $c->replaceRecursive(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceRecursiveArray($collection)
    {
        $c = new $collection(['a', 'b', ['c', 'd']]);
        $this->assertEquals(['z', 'b', ['c', 'e']], $c->replaceRecursive(['z', 2 => [1 => 'e']])->all());

        $c = new $collection(['a', 'b', ['c', 'd']]);
        $this->assertEquals(['z', 'b', ['c', 'e'], 'f'], $c->replaceRecursive(['z', 2 => [1 => 'e'], 'f'])->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReplaceRecursiveCollection($collection)
    {
        $c = new $collection(['a', 'b', ['c', 'd']]);
        $this->assertEquals(
            ['z', 'b', ['c', 'e']],
            $c->replaceRecursive(new $collection(['z', 2 => [1 => 'e']]))->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnionNull($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello'], $c->union(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnionArray($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello', 'id' => 1], $c->union(['id' => 1])->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnionCollection($collection)
    {
        $c = new $collection(['name' => 'Hello']);
        $this->assertEquals(['name' => 'Hello', 'id' => 1], $c->union(new $collection(['name' => 'World', 'id' => 1]))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffCollection($collection)
    {
        $c = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['id' => 1], $c->diff(new $collection(['first_word' => 'Hello', 'last_word' => 'World']))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffUsingWithCollection($collection)
    {
        $c = new $collection(['en_GB', 'fr', 'HR']);
        // demonstrate that diffKeys won't support case insensitivity
        $this->assertEquals(['en_GB', 'fr', 'HR'], $c->diff(new $collection(['en_gb', 'hr']))->values()->toArray());
        // allow for case insensitive difference
        $this->assertEquals(['fr'], $c->diffUsing(new $collection(['en_gb', 'hr']), 'strcasecmp')->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffUsingWithNull($collection)
    {
        $c = new $collection(['en_GB', 'fr', 'HR']);
        $this->assertEquals(['en_GB', 'fr', 'HR'], $c->diffUsing(null, 'strcasecmp')->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffNull($collection)
    {
        $c = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['id' => 1, 'first_word' => 'Hello'], $c->diff(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffKeys($collection)
    {
        $c1 = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $c2 = new $collection(['id' => 123, 'foo_bar' => 'Hello']);
        $this->assertEquals(['first_word' => 'Hello'], $c1->diffKeys($c2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffKeysUsing($collection)
    {
        $c1 = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $c2 = new $collection(['ID' => 123, 'foo_bar' => 'Hello']);
        // demonstrate that diffKeys won't support case insensitivity
        $this->assertEquals(['id' => 1, 'first_word' => 'Hello'], $c1->diffKeys($c2)->all());
        // allow for case insensitive difference
        $this->assertEquals(['first_word' => 'Hello'], $c1->diffKeysUsing($c2, 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffAssoc($collection)
    {
        $c1 = new $collection(['id' => 1, 'first_word' => 'Hello', 'not_affected' => 'value']);
        $c2 = new $collection(['id' => 123, 'foo_bar' => 'Hello', 'not_affected' => 'value']);
        $this->assertEquals(['id' => 1, 'first_word' => 'Hello'], $c1->diffAssoc($c2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDiffAssocUsing($collection)
    {
        $c1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);
        $c2 = new $collection(['A' => 'green', 'yellow', 'red']);
        // demonstrate that the case of the keys will affect the output when diffAssoc is used
        $this->assertEquals(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red'], $c1->diffAssoc($c2)->all());
        // allow for case insensitive difference
        $this->assertEquals(['b' => 'brown', 'c' => 'blue', 'red'], $c1->diffAssocUsing($c2, 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDuplicates($collection)
    {
        $duplicates = $collection::make([1, 2, 1, 'laravel', null, 'laravel', 'php', null])->duplicates()->all();
        $this->assertSame([2 => 1, 5 => 'laravel', 7 => null], $duplicates);

        // does loose comparison
        $duplicates = $collection::make([2, '2', [], null])->duplicates()->all();
        $this->assertSame([1 => '2', 3 => null], $duplicates);

        // works with mix of primitives
        $duplicates = $collection::make([1, '2', ['laravel'], ['laravel'], null, '2'])->duplicates()->all();
        $this->assertSame([3 => ['laravel'], 5 => '2'], $duplicates);

        // works with mix of objects and primitives **excepts numbers**.
        $expected = new Collection(['laravel']);
        $duplicates = $collection::make([new Collection(['laravel']), $expected, $expected, [], '2', '2'])->duplicates()->all();
        $this->assertSame([1 => $expected, 2 => $expected, 5 => '2'], $duplicates);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDuplicatesWithKey($collection)
    {
        $items = [['framework' => 'vue'], ['framework' => 'laravel'], ['framework' => 'laravel']];
        $duplicates = $collection::make($items)->duplicates('framework')->all();
        $this->assertSame([2 => 'laravel'], $duplicates);

        // works with key and strict
        $items = [['Framework' => 'vue'], ['framework' => 'vue'], ['Framework' => 'vue']];
        $duplicates = $collection::make($items)->duplicates('Framework', true)->all();
        $this->assertSame([2 => 'vue'], $duplicates);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDuplicatesWithCallback($collection)
    {
        $items = [['framework' => 'vue'], ['framework' => 'laravel'], ['framework' => 'laravel']];
        $duplicates = $collection::make($items)->duplicates(function ($item) {
            return $item['framework'];
        })->all();
        $this->assertSame([2 => 'laravel'], $duplicates);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDuplicatesWithStrict($collection)
    {
        $duplicates = $collection::make([1, 2, 1, 'laravel', null, 'laravel', 'php', null])->duplicatesStrict()->all();
        $this->assertSame([2 => 1, 5 => 'laravel', 7 => null], $duplicates);

        // does strict comparison
        $duplicates = $collection::make([2, '2', [], null])->duplicatesStrict()->all();
        $this->assertSame([], $duplicates);

        // works with mix of primitives
        $duplicates = $collection::make([1, '2', ['laravel'], ['laravel'], null, '2'])->duplicatesStrict()->all();
        $this->assertSame([3 => ['laravel'], 5 => '2'], $duplicates);

        // works with mix of primitives, objects, and numbers
        $expected = new $collection(['laravel']);
        $duplicates = $collection::make([new $collection(['laravel']), $expected, $expected, [], '2', '2'])->duplicatesStrict()->all();
        $this->assertSame([2 => $expected, 5 => '2'], $duplicates);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEach($collection)
    {
        $c = new $collection($original = [1, 2, 'foo' => 'bar', 'bam' => 'baz']);

        $result = [];
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
        });
        $this->assertEquals($original, $result);

        $result = [];
        $c->each(function ($item, $key) use (&$result) {
            $result[$key] = $item;
            if (is_string($key)) {
                return false;
            }
        });
        $this->assertEquals([1, 2, 'foo' => 'bar'], $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEachSpread($collection)
    {
        $c = new $collection([[1, 'a'], [2, 'b']]);

        $result = [];
        $c->eachSpread(function ($number, $character) use (&$result) {
            $result[] = [$number, $character];
        });
        $this->assertEquals($c->all(), $result);

        $result = [];
        $c->eachSpread(function ($number, $character) use (&$result) {
            $result[] = [$number, $character];

            return false;
        });
        $this->assertEquals([[1, 'a']], $result);

        $result = [];
        $c->eachSpread(function ($number, $character, $key) use (&$result) {
            $result[] = [$number, $character, $key];
        });
        $this->assertEquals([[1, 'a', 0], [2, 'b', 1]], $result);

        $c = new $collection([new Collection([1, 'a']), new Collection([2, 'b'])]);
        $result = [];
        $c->eachSpread(function ($number, $character, $key) use (&$result) {
            $result[] = [$number, $character, $key];
        });
        $this->assertEquals([[1, 'a', 0], [2, 'b', 1]], $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectNull($collection)
    {
        $c = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals([], $c->intersect(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectCollection($collection)
    {
        $c = new $collection(['id' => 1, 'first_word' => 'Hello']);
        $this->assertEquals(['first_word' => 'Hello'], $c->intersect(new $collection(['first_world' => 'Hello', 'last_word' => 'World']))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectUsingWithNull($collection)
    {
        $collect = new $collection(['green', 'brown', 'blue']);

        $this->assertEquals([], $collect->intersectUsing(null, 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectUsingCollection($collection)
    {
        $collect = new $collection(['green', 'brown', 'blue']);

        $this->assertEquals(['green', 'brown'], $collect->intersectUsing(new $collection(['GREEN', 'brown', 'yellow']), 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectAssocWithNull($collection)
    {
        $array1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);

        $this->assertEquals([], $array1->intersectAssoc(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectAssocCollection($collection)
    {
        $array1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);
        $array2 = new $collection(['a' => 'green', 'b' => 'yellow', 'blue', 'red']);

        $this->assertEquals(['a' => 'green'], $array1->intersectAssoc($array2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectAssocUsingWithNull($collection)
    {
        $array1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);

        $this->assertEquals([], $array1->intersectAssocUsing(null, 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectAssocUsingCollection($collection)
    {
        $array1 = new $collection(['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']);
        $array2 = new $collection(['a' => 'GREEN', 'B' => 'brown', 'yellow', 'red']);

        $this->assertEquals(['b' => 'brown'], $array1->intersectAssocUsing($array2, 'strcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectByKeysNull($collection)
    {
        $c = new $collection(['name' => 'Mateus', 'age' => 18]);
        $this->assertEquals([], $c->intersectByKeys(null)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testIntersectByKeys($collection)
    {
        $c = new $collection(['name' => 'Mateus', 'age' => 18]);
        $this->assertEquals(['name' => 'Mateus'], $c->intersectByKeys(new $collection(['name' => 'Mateus', 'surname' => 'Guimaraes']))->all());

        $c = new $collection(['name' => 'taylor', 'family' => 'otwell', 'age' => 26]);
        $this->assertEquals(['name' => 'taylor', 'family' => 'otwell'], $c->intersectByKeys(new $collection(['height' => 180, 'name' => 'amir', 'family' => 'moharami']))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnique($collection)
    {
        $c = new $collection(['Hello', 'World', 'World']);
        $this->assertEquals(['Hello', 'World'], $c->unique()->all());

        $c = new $collection([[1, 2], [1, 2], [2, 3], [3, 4], [2, 3]]);
        $this->assertEquals([[1, 2], [2, 3], [3, 4]], $c->unique()->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUniqueWithCallback($collection)
    {
        $c = new $collection([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
            4 => ['id' => 4, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
            6 => ['id' => 6, 'first' => 'Taylor', 'last' => 'Swift'],
        ]);

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
        ], $c->unique('first')->all());

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            3 => ['id' => 3, 'first' => 'Abigail', 'last' => 'Otwell'],
            5 => ['id' => 5, 'first' => 'Taylor', 'last' => 'Swift'],
        ], $c->unique(function ($item) {
            return $item['first'].$item['last'];
        })->all());

        $this->assertEquals([
            1 => ['id' => 1, 'first' => 'Taylor', 'last' => 'Otwell'],
            2 => ['id' => 2, 'first' => 'Taylor', 'last' => 'Otwell'],
        ], $c->unique(function ($item, $key) {
            return $key % 2;
        })->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUniqueStrict($collection)
    {
        $c = new $collection([
            [
                'id' => '0',
                'name' => 'zero',
            ],
            [
                'id' => '00',
                'name' => 'double zero',
            ],
            [
                'id' => '0',
                'name' => 'again zero',
            ],
        ]);

        $this->assertEquals([
            [
                'id' => '0',
                'name' => 'zero',
            ],
            [
                'id' => '00',
                'name' => 'double zero',
            ],
        ], $c->uniqueStrict('id')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollapse($collection)
    {
        $data = new $collection([[$object1 = new stdClass], [$object2 = new stdClass]]);
        $this->assertEquals([$object1, $object2], $data->collapse()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollapseWithNestedCollections($collection)
    {
        $data = new $collection([new $collection([1, 2, 3]), new $collection([4, 5, 6])]);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $data->collapse()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testJoin($collection)
    {
        $this->assertSame('a, b, c', (new $collection(['a', 'b', 'c']))->join(', '));

        $this->assertSame('a, b and c', (new $collection(['a', 'b', 'c']))->join(', ', ' and '));

        $this->assertSame('a and b', (new $collection(['a', 'b']))->join(', ', ' and '));

        $this->assertSame('a', (new $collection(['a']))->join(', ', ' and '));

        $this->assertSame('', (new $collection([]))->join(', ', ' and '));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCrossJoin($collection)
    {
        // Cross join with an array
        $this->assertEquals(
            [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
            (new $collection([1, 2]))->crossJoin(['a', 'b'])->all()
        );

        // Cross join with a collection
        $this->assertEquals(
            [[1, 'a'], [1, 'b'], [2, 'a'], [2, 'b']],
            (new $collection([1, 2]))->crossJoin(new $collection(['a', 'b']))->all()
        );

        // Cross join with 2 collections
        $this->assertEquals(
            [
                [1, 'a', 'I'], [1, 'a', 'II'],
                [1, 'b', 'I'], [1, 'b', 'II'],
                [2, 'a', 'I'], [2, 'a', 'II'],
                [2, 'b', 'I'], [2, 'b', 'II'],
            ],
            (new $collection([1, 2]))->crossJoin(
                new $collection(['a', 'b']),
                new $collection(['I', 'II'])
            )->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSort($collection)
    {
        $data = (new $collection([5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([1, 2, 3, 4, 5], $data->values()->all());

        $data = (new $collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sort();
        $this->assertEquals([-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5], $data->values()->all());

        $data = (new $collection(['foo', 'bar-10', 'bar-1']))->sort();
        $this->assertEquals(['bar-1', 'bar-10', 'foo'], $data->values()->all());

        $data = (new $collection(['T2', 'T1', 'T10']))->sort();
        $this->assertEquals(['T1', 'T10', 'T2'], $data->values()->all());

        $data = (new $collection(['T2', 'T1', 'T10']))->sort(SORT_NATURAL);
        $this->assertEquals(['T1', 'T2', 'T10'], $data->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortDesc($collection)
    {
        $data = (new $collection([5, 3, 1, 2, 4]))->sortDesc();
        $this->assertEquals([5, 4, 3, 2, 1], $data->values()->all());

        $data = (new $collection([-1, -3, -2, -4, -5, 0, 5, 3, 1, 2, 4]))->sortDesc();
        $this->assertEquals([5, 4, 3, 2, 1, 0, -1, -2, -3, -4, -5], $data->values()->all());

        $data = (new $collection(['bar-1', 'foo', 'bar-10']))->sortDesc();
        $this->assertEquals(['foo', 'bar-10', 'bar-1'], $data->values()->all());

        $data = (new $collection(['T2', 'T1', 'T10']))->sortDesc();
        $this->assertEquals(['T2', 'T10', 'T1'], $data->values()->all());

        $data = (new $collection(['T2', 'T1', 'T10']))->sortDesc(SORT_NATURAL);
        $this->assertEquals(['T10', 'T2', 'T1'], $data->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortWithCallback($collection)
    {
        $data = (new $collection([5, 3, 1, 2, 4]))->sort(function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        $this->assertEquals(range(1, 5), array_values($data->all()));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortBy($collection)
    {
        $data = new $collection(['taylor', 'dayle']);
        $data = $data->sortBy(function ($x) {
            return $x;
        });

        $this->assertEquals(['dayle', 'taylor'], array_values($data->all()));

        $data = new $collection(['dayle', 'taylor']);
        $data = $data->sortByDesc(function ($x) {
            return $x;
        });

        $this->assertEquals(['taylor', 'dayle'], array_values($data->all()));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortByString($collection)
    {
        $data = new $collection([['name' => 'taylor'], ['name' => 'dayle']]);
        $data = $data->sortBy('name', SORT_STRING);

        $this->assertEquals([['name' => 'dayle'], ['name' => 'taylor']], array_values($data->all()));

        $data = new $collection([['name' => 'taylor'], ['name' => 'dayle']]);
        $data = $data->sortBy('name', SORT_STRING);

        $this->assertEquals([['name' => 'dayle'], ['name' => 'taylor']], array_values($data->all()));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortByCallableString($collection)
    {
        $data = new $collection([['sort' => 2], ['sort' => 1]]);
        $data = $data->sortBy([['sort', 'asc']]);

        $this->assertEquals([['sort' => 1], ['sort' => 2]], array_values($data->all()));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortByAlwaysReturnsAssoc($collection)
    {
        $data = new $collection(['a' => 'taylor', 'b' => 'dayle']);
        $data = $data->sortBy(function ($x) {
            return $x;
        });

        $this->assertEquals(['b' => 'dayle', 'a' => 'taylor'], $data->all());

        $data = new $collection(['taylor', 'dayle']);
        $data = $data->sortBy(function ($x) {
            return $x;
        });

        $this->assertEquals([1 => 'dayle', 0 => 'taylor'], $data->all());

        $data = new $collection(['a' => ['sort' => 2], 'b' => ['sort' => 1]]);
        $data = $data->sortBy([['sort', 'asc']]);

        $this->assertEquals(['b' => ['sort' => 1], 'a' => ['sort' => 2]], $data->all());

        $data = new $collection([['sort' => 2], ['sort' => 1]]);
        $data = $data->sortBy([['sort', 'asc']]);

        $this->assertEquals([1 => ['sort' => 1], 0 => ['sort' => 2]], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortKeys($collection)
    {
        $data = new $collection(['b' => 'dayle', 'a' => 'taylor']);

        $this->assertSame(['a' => 'taylor', 'b' => 'dayle'], $data->sortKeys()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortKeysDesc($collection)
    {
        $data = new $collection(['a' => 'taylor', 'b' => 'dayle']);

        $this->assertSame(['b' => 'dayle', 'a' => 'taylor'], $data->sortKeysDesc()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSortKeysUsing($collection)
    {
        $data = new $collection(['B' => 'dayle', 'a' => 'taylor']);

        $this->assertSame(['a' => 'taylor', 'B' => 'dayle'], $data->sortKeysUsing('strnatcasecmp')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReverse($collection)
    {
        $data = new $collection(['zaeed', 'alan']);
        $reversed = $data->reverse();

        $this->assertSame([1 => 'alan', 0 => 'zaeed'], $reversed->all());

        $data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
        $reversed = $data->reverse();

        $this->assertSame(['framework' => 'laravel', 'name' => 'taylor'], $reversed->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFlip($collection)
    {
        $data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['taylor' => 'name', 'laravel' => 'framework'], $data->flip()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testChunk($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $data = $data->chunk(3);

        $this->assertInstanceOf($collection, $data);
        $this->assertInstanceOf($collection, $data->first());
        $this->assertCount(4, $data);
        $this->assertEquals([1, 2, 3], $data->first()->toArray());
        $this->assertEquals([9 => 10], $data->get(3)->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testChunkWhenGivenZeroAsSize($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertEquals(
            [],
            $data->chunk(0)->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testChunkWhenGivenLessThanZero($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $this->assertEquals(
            [],
            $data->chunk(-1)->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitIn($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $data = $data->splitIn(3);

        $this->assertInstanceOf($collection, $data);
        $this->assertInstanceOf($collection, $data->first());
        $this->assertCount(3, $data);
        $this->assertEquals([1, 2, 3, 4], $data->get(0)->values()->toArray());
        $this->assertEquals([5, 6, 7, 8], $data->get(1)->values()->toArray());
        $this->assertEquals([9, 10], $data->get(2)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testChunkWhileOnEqualElements($collection)
    {
        $data = (new $collection(['A', 'A', 'B', 'B', 'C', 'C', 'C']))
            ->chunkWhile(function ($current, $key, $chunk) {
                return $chunk->last() === $current;
            });

        $this->assertInstanceOf($collection, $data);
        $this->assertInstanceOf($collection, $data->first());
        $this->assertEquals([0 => 'A', 1 => 'A'], $data->first()->toArray());
        $this->assertEquals([2 => 'B', 3 => 'B'], $data->get(1)->toArray());
        $this->assertEquals([4 => 'C', 5 => 'C', 6 => 'C'], $data->last()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testChunkWhileOnContiguouslyIncreasingIntegers($collection)
    {
        $data = (new $collection([1, 4, 9, 10, 11, 12, 15, 16, 19, 20, 21]))
            ->chunkWhile(function ($current, $key, $chunk) {
                return $chunk->last() + 1 == $current;
            });

        $this->assertInstanceOf($collection, $data);
        $this->assertInstanceOf($collection, $data->first());
        $this->assertEquals([0 => 1], $data->first()->toArray());
        $this->assertEquals([1 => 4], $data->get(1)->toArray());
        $this->assertEquals([2 => 9, 3 => 10, 4 => 11, 5 => 12], $data->get(2)->toArray());
        $this->assertEquals([6 => 15, 7 => 16], $data->get(3)->toArray());
        $this->assertEquals([8 => 19, 9 => 20, 10 => 21], $data->last()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEvery($collection)
    {
        $c = new $collection([]);
        $this->assertTrue($c->every('key', 'value'));
        $this->assertTrue($c->every(function () {
            return false;
        }));

        $c = new $collection([['age' => 18], ['age' => 20], ['age' => 20]]);
        $this->assertFalse($c->every('age', 18));
        $this->assertTrue($c->every('age', '>=', 18));
        $this->assertTrue($c->every(function ($item) {
            return $item['age'] >= 18;
        }));
        $this->assertFalse($c->every(function ($item) {
            return $item['age'] >= 20;
        }));

        $c = new $collection([null, null]);
        $this->assertTrue($c->every(function ($item) {
            return $item === null;
        }));

        $c = new $collection([['active' => true], ['active' => true]]);
        $this->assertTrue($c->every('active'));
        $this->assertTrue($c->every->active);
        $this->assertFalse($c->concat([['active' => false]])->every->active);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testExcept($collection)
    {
        $data = new $collection(['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com']);

        $this->assertEquals(['first' => 'Taylor'], $data->except(['last', 'email', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], $data->except('last', 'email', 'missing')->all());

        $this->assertEquals(['first' => 'Taylor'], $data->except(collect(['last', 'email', 'missing']))->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except(['last'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->except('last')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testExceptSelf($collection)
    {
        $data = new $collection(['first' => 'Taylor', 'last' => 'Otwell']);
        $this->assertEquals(['first' => 'Taylor', 'last' => 'Otwell'], $data->except($data)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPluckWithArrayAndObjectValues($collection)
    {
        $data = new $collection([(object) ['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], $data->pluck('email')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPluckWithArrayAccessValues($collection)
    {
        $data = new $collection([
            new TestArrayAccessImplementation(['name' => 'taylor', 'email' => 'foo']),
            new TestArrayAccessImplementation(['name' => 'dayle', 'email' => 'bar']),
        ]);

        $this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], $data->pluck('email', 'name')->all());
        $this->assertEquals(['foo', 'bar'], $data->pluck('email')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPluckWithDotNotation($collection)
    {
        $data = new $collection([
            [
                'name' => 'amir',
                'skill' => [
                    'backend' => ['php', 'python'],
                ],
            ],
            [
                'name' => 'taylor',
                'skill' => [
                    'backend' => ['php', 'asp', 'java'],
                ],
            ],
        ]);

        $this->assertEquals([['php', 'python'], ['php', 'asp', 'java']], $data->pluck('skill.backend')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPluckDuplicateKeysExist($collection)
    {
        $data = new collection([
            ['brand' => 'Tesla', 'color' => 'red'],
            ['brand' => 'Pagani', 'color' => 'white'],
            ['brand' => 'Tesla', 'color' => 'black'],
            ['brand' => 'Pagani', 'color' => 'orange'],
        ]);

        $this->assertEquals(['Tesla' => 'black', 'Pagani' => 'orange'], $data->pluck('color', 'brand')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHas($collection)
    {
        $data = new $collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);
        $this->assertTrue($data->has('first'));
        $this->assertFalse($data->has('third'));
        $this->assertTrue($data->has(['first', 'second']));
        $this->assertFalse($data->has(['third', 'first']));
        $this->assertTrue($data->has('first', 'second'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHasAny($collection)
    {
        $data = new $collection(['id' => 1, 'first' => 'Hello', 'second' => 'World']);

        $this->assertTrue($data->hasAny('first'));
        $this->assertFalse($data->hasAny('third'));
        $this->assertTrue($data->hasAny(['first', 'second']));
        $this->assertTrue($data->hasAny(['first', 'fourth']));
        $this->assertFalse($data->hasAny(['third', 'fourth']));
        $this->assertFalse($data->hasAny('third', 'fourth'));
        $this->assertFalse($data->hasAny([]));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testImplode($collection)
    {
        $data = new $collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertSame('foobar', $data->implode('email'));
        $this->assertSame('foo,bar', $data->implode('email', ','));

        $data = new $collection(['taylor', 'dayle']);
        $this->assertSame('taylordayle', $data->implode(''));
        $this->assertSame('taylor,dayle', $data->implode(','));

        $data = new $collection([
            ['name' => Str::of('taylor'), 'email' => Str::of('foo')],
            ['name' => Str::of('dayle'), 'email' => Str::of('bar')],
        ]);
        $this->assertSame('foobar', $data->implode('email'));
        $this->assertSame('foo,bar', $data->implode('email', ','));

        $data = new $collection([Str::of('taylor'), Str::of('dayle')]);
        $this->assertSame('taylordayle', $data->implode(''));
        $this->assertSame('taylor,dayle', $data->implode(','));
        $this->assertSame('taylor_dayle', $data->implode('_'));

        $data = new $collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
        $this->assertSame('taylor-foodayle-bar', $data->implode(fn ($user) => $user['name'].'-'.$user['email']));
        $this->assertSame('taylor-foo,dayle-bar', $data->implode(fn ($user) => $user['name'].'-'.$user['email'], ','));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTake($collection)
    {
        $data = new $collection(['taylor', 'dayle', 'shawn']);
        $data = $data->take(2);
        $this->assertEquals(['taylor', 'dayle'], $data->all());
    }

    public function testGetOrPut()
    {
        $data = new Collection(['name' => 'taylor', 'email' => 'foo']);

        $this->assertSame('taylor', $data->getOrPut('name', null));
        $this->assertSame('foo', $data->getOrPut('email', null));
        $this->assertSame('male', $data->getOrPut('gender', 'male'));

        $this->assertSame('taylor', $data->get('name'));
        $this->assertSame('foo', $data->get('email'));
        $this->assertSame('male', $data->get('gender'));

        $data = new Collection(['name' => 'taylor', 'email' => 'foo']);

        $this->assertSame('taylor', $data->getOrPut('name', function () {
            return null;
        }));

        $this->assertSame('foo', $data->getOrPut('email', function () {
            return null;
        }));

        $this->assertSame('male', $data->getOrPut('gender', function () {
            return 'male';
        }));

        $this->assertSame('taylor', $data->get('name'));
        $this->assertSame('foo', $data->get('email'));
        $this->assertSame('male', $data->get('gender'));
    }

    public function testPut()
    {
        $data = new Collection(['name' => 'taylor', 'email' => 'foo']);
        $data = $data->put('name', 'dayle');
        $this->assertEquals(['name' => 'dayle', 'email' => 'foo'], $data->all());
    }

    public function testPutWithNoKey()
    {
        $data = new Collection(['taylor', 'shawn']);
        $data = $data->put(null, 'dayle');
        $this->assertEquals(['taylor', 'shawn', 'dayle'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRandom($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6]);

        $random = $data->random();
        $this->assertIsInt($random);
        $this->assertContains($random, $data->all());

        $random = $data->random(0);
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(0, $random);

        $random = $data->random(1);
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(1, $random);

        $random = $data->random(2);
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(2, $random);

        $random = $data->random('0');
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(0, $random);

        $random = $data->random('1');
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(1, $random);

        $random = $data->random('2');
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(2, $random);

        $random = $data->random(fn ($items) => min(10, count($items)));
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(6, $random);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRandomOnEmptyCollection($collection)
    {
        $data = new $collection;

        $random = $data->random(0);
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(0, $random);

        $random = $data->random('0');
        $this->assertInstanceOf($collection, $random);
        $this->assertCount(0, $random);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeLast($collection)
    {
        $data = new $collection(['taylor', 'dayle', 'shawn']);
        $data = $data->take(-2);
        $this->assertEquals([1 => 'dayle', 2 => 'shawn'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeUntilUsingValue($collection)
    {
        $data = new $collection([1, 2, 3, 4]);

        $data = $data->takeUntil(3);

        $this->assertSame([1, 2], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeUntilUsingCallback($collection)
    {
        $data = new $collection([1, 2, 3, 4]);

        $data = $data->takeUntil(function ($item) {
            return $item >= 3;
        });

        $this->assertSame([1, 2], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeUntilReturnsAllItemsForUnmetValue($collection)
    {
        $data = new $collection([1, 2, 3, 4]);

        $actual = $data->takeUntil(99);

        $this->assertSame($data->toArray(), $actual->toArray());

        $actual = $data->takeUntil(function ($item) {
            return $item >= 99;
        });

        $this->assertSame($data->toArray(), $actual->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeUntilCanBeProxied($collection)
    {
        $data = new $collection([
            new TestSupportCollectionHigherOrderItem('Adam'),
            new TestSupportCollectionHigherOrderItem('Taylor'),
            new TestSupportCollectionHigherOrderItem('Jason'),
        ]);

        $actual = $data->takeUntil->is('Jason');

        $this->assertCount(2, $actual);
        $this->assertSame('Adam', $actual->get(0)->name);
        $this->assertSame('Taylor', $actual->get(1)->name);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeWhileUsingValue($collection)
    {
        $data = new $collection([1, 1, 2, 2, 3, 3]);

        $data = $data->takeWhile(1);

        $this->assertSame([1, 1], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeWhileUsingCallback($collection)
    {
        $data = new $collection([1, 2, 3, 4]);

        $data = $data->takeWhile(function ($item) {
            return $item < 3;
        });

        $this->assertSame([1, 2], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeWhileReturnsNoItemsForUnmetValue($collection)
    {
        $data = new $collection([1, 2, 3, 4]);

        $actual = $data->takeWhile(2);

        $this->assertSame([], $actual->toArray());

        $actual = $data->takeWhile(function ($item) {
            return $item == 99;
        });

        $this->assertSame([], $actual->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTakeWhileCanBeProxied($collection)
    {
        $data = new $collection([
            new TestSupportCollectionHigherOrderItem('Adam'),
            new TestSupportCollectionHigherOrderItem('Adam'),
            new TestSupportCollectionHigherOrderItem('Taylor'),
            new TestSupportCollectionHigherOrderItem('Taylor'),
        ]);

        $actual = $data->takeWhile->is('Adam');

        $this->assertCount(2, $actual);
        $this->assertSame('Adam', $actual->get(0)->name);
        $this->assertSame('Adam', $actual->get(1)->name);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMacroable($collection)
    {
        // Foo() macro : unique values starting with A
        $collection::macro('foo', function () {
            return $this->filter(function ($item) {
                return str_starts_with($item, 'a');
            })
                ->unique()
                ->values();
        });

        $c = new $collection(['a', 'a', 'aa', 'aaa', 'bar']);

        $this->assertSame(['a', 'aa', 'aaa'], $c->foo()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCanAddMethodsToProxy($collection)
    {
        $collection::macro('adults', function ($callback) {
            return $this->filter(function ($item) use ($callback) {
                return $callback($item) >= 18;
            });
        });

        $collection::proxy('adults');

        $c = new $collection([['age' => 3], ['age' => 12], ['age' => 18], ['age' => 56]]);

        $this->assertSame([['age' => 18], ['age' => 56]], $c->adults->age->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMakeMethod($collection)
    {
        $data = $collection::make('foo');
        $this->assertEquals(['foo'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMakeMethodFromNull($collection)
    {
        $data = $collection::make(null);
        $this->assertEquals([], $data->all());

        $data = $collection::make();
        $this->assertEquals([], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMakeMethodFromCollection($collection)
    {
        $firstCollection = $collection::make(['foo' => 'bar']);
        $secondCollection = $collection::make($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMakeMethodFromArray($collection)
    {
        $data = $collection::make(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithScalar($collection)
    {
        $data = $collection::wrap('foo');
        $this->assertEquals(['foo'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithArray($collection)
    {
        $data = $collection::wrap(['foo']);
        $this->assertEquals(['foo'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithArrayable($collection)
    {
        $data = $collection::wrap($o = new TestArrayableObject);
        $this->assertEquals([$o], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithJsonable($collection)
    {
        $data = $collection::wrap($o = new TestJsonableObject);
        $this->assertEquals([$o], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithJsonSerialize($collection)
    {
        $data = $collection::wrap($o = new TestJsonSerializeObject);
        $this->assertEquals([$o], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithCollectionClass($collection)
    {
        $data = $collection::wrap($collection::make(['foo']));
        $this->assertEquals(['foo'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWrapWithCollectionSubclass($collection)
    {
        $data = TestCollectionSubclass::wrap($collection::make(['foo']));
        $this->assertEquals(['foo'], $data->all());
        $this->assertInstanceOf(TestCollectionSubclass::class, $data);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnwrapCollection($collection)
    {
        $data = new $collection(['foo']);
        $this->assertEquals(['foo'], $collection::unwrap($data));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnwrapCollectionWithArray($collection)
    {
        $this->assertEquals(['foo'], $collection::unwrap(['foo']));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnwrapCollectionWithScalar($collection)
    {
        $this->assertSame('foo', $collection::unwrap('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEmptyMethod($collection)
    {
        $collection = $collection::empty();

        $this->assertCount(0, $collection->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTimesMethod($collection)
    {
        $two = $collection::times(2, function ($number) {
            return 'slug-'.$number;
        });

        $zero = $collection::times(0, function ($number) {
            return 'slug-'.$number;
        });

        $negative = $collection::times(-4, function ($number) {
            return 'slug-'.$number;
        });

        $range = $collection::times(5);

        $this->assertEquals(['slug-1', 'slug-2'], $two->all());
        $this->assertTrue($zero->isEmpty());
        $this->assertTrue($negative->isEmpty());
        $this->assertEquals(range(1, 5), $range->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRangeMethod($collection)
    {
        $this->assertSame(
            [1, 2, 3, 4, 5],
            $collection::range(1, 5)->all()
        );

        $this->assertSame(
            [-2, -1, 0, 1, 2],
            $collection::range(-2, 2)->all()
        );

        $this->assertSame(
            [-4, -3, -2],
            $collection::range(-4, -2)->all()
        );

        $this->assertSame(
            [5, 4, 3, 2, 1],
            $collection::range(5, 1)->all()
        );

        $this->assertSame(
            [2, 1, 0, -1, -2],
            $collection::range(2, -2)->all()
        );

        $this->assertSame(
            [-2, -3, -4],
            $collection::range(-2, -4)->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMakeFromObject($collection)
    {
        $object = new stdClass;
        $object->foo = 'bar';
        $data = $collection::make($object);
        $this->assertEquals(['foo' => 'bar'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMethod($collection)
    {
        $data = new $collection('foo');
        $this->assertEquals(['foo'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMethodFromNull($collection)
    {
        $data = new $collection(null);
        $this->assertEquals([], $data->all());

        $data = new $collection;
        $this->assertEquals([], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMethodFromCollection($collection)
    {
        $firstCollection = new $collection(['foo' => 'bar']);
        $secondCollection = new $collection($firstCollection);
        $this->assertEquals(['foo' => 'bar'], $secondCollection->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMethodFromArray($collection)
    {
        $data = new $collection(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConstructMethodFromObject($collection)
    {
        $object = new stdClass;
        $object->foo = 'bar';
        $data = new $collection($object);
        $this->assertEquals(['foo' => 'bar'], $data->all());
    }

    public function testSplice()
    {
        $data = new Collection(['foo', 'baz']);
        $data->splice(1);
        $this->assertEquals(['foo'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 0, 'bar');
        $this->assertEquals(['foo', 'bar', 'baz'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 1);
        $this->assertEquals(['foo'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $cut = $data->splice(1, 1, 'bar');
        $this->assertEquals(['foo', 'bar'], $data->all());
        $this->assertEquals(['baz'], $cut->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 0, ['bar']);
        $this->assertEquals(['foo', 'bar', 'baz'], $data->all());

        $data = new Collection(['foo', 'baz']);
        $data->splice(1, 0, new Collection(['bar']));
        $this->assertEquals(['foo', 'bar', 'baz'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGetPluckValueWithAccessors($collection)
    {
        $model = new TestAccessorEloquentTestStub(['some' => 'foo']);
        $modelTwo = new TestAccessorEloquentTestStub(['some' => 'bar']);
        $data = new $collection([$model, $modelTwo]);

        $this->assertEquals(['foo', 'bar'], $data->pluck('some')->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMap($collection)
    {
        $data = new $collection([1, 2, 3]);
        $mapped = $data->map(function ($item, $key) {
            return $item * 2;
        });
        $this->assertEquals([2, 4, 6], $mapped->all());
        $this->assertEquals([1, 2, 3], $data->all());

        $data = new $collection(['first' => 'taylor', 'last' => 'otwell']);
        $data = $data->map(function ($item, $key) {
            return $key.'-'.strrev($item);
        });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapSpread($collection)
    {
        $c = new $collection([[1, 'a'], [2, 'b']]);

        $result = $c->mapSpread(function ($number, $character) {
            return "{$number}-{$character}";
        });
        $this->assertEquals(['1-a', '2-b'], $result->all());

        $result = $c->mapSpread(function ($number, $character, $key) {
            return "{$number}-{$character}-{$key}";
        });
        $this->assertEquals(['1-a-0', '2-b-1'], $result->all());

        $c = new $collection([new Collection([1, 'a']), new Collection([2, 'b'])]);
        $result = $c->mapSpread(function ($number, $character, $key) {
            return "{$number}-{$character}-{$key}";
        });
        $this->assertEquals(['1-a-0', '2-b-1'], $result->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testFlatMap($collection)
    {
        $data = new $collection([
            ['name' => 'taylor', 'hobbies' => ['programming', 'basketball']],
            ['name' => 'adam', 'hobbies' => ['music', 'powerlifting']],
        ]);
        $data = $data->flatMap(function ($person) {
            return $person['hobbies'];
        });
        $this->assertEquals(['programming', 'basketball', 'music', 'powerlifting'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapToDictionary($collection)
    {
        $data = new $collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
            ['id' => 4, 'name' => 'B'],
        ]);

        $groups = $data->mapToDictionary(function ($item, $key) {
            return [$item['name'] => $item['id']];
        });

        $this->assertInstanceOf($collection, $groups);
        $this->assertEquals(['A' => [1], 'B' => [2, 4], 'C' => [3]], $groups->toArray());
        $this->assertIsArray($groups->get('A'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapToDictionaryWithNumericKeys($collection)
    {
        $data = new $collection([1, 2, 3, 2, 1]);

        $groups = $data->mapToDictionary(function ($item, $key) {
            return [$item => $key];
        });

        $this->assertEquals([1 => [0, 4], 2 => [1, 3], 3 => [2]], $groups->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapToGroups($collection)
    {
        $data = new $collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
            ['id' => 4, 'name' => 'B'],
        ]);

        $groups = $data->mapToGroups(function ($item, $key) {
            return [$item['name'] => $item['id']];
        });

        $this->assertInstanceOf($collection, $groups);
        $this->assertEquals(['A' => [1], 'B' => [2, 4], 'C' => [3]], $groups->toArray());
        $this->assertInstanceOf($collection, $groups->get('A'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapToGroupsWithNumericKeys($collection)
    {
        $data = new $collection([1, 2, 3, 2, 1]);

        $groups = $data->mapToGroups(function ($item, $key) {
            return [$item => $key];
        });

        $this->assertEquals([1 => [0, 4], 2 => [1, 3], 3 => [2]], $groups->toArray());
        $this->assertEquals([1, 2, 3, 2, 1], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapWithKeys($collection)
    {
        $data = new $collection([
            ['name' => 'Blastoise', 'type' => 'Water', 'idx' => 9],
            ['name' => 'Charmander', 'type' => 'Fire', 'idx' => 4],
            ['name' => 'Dragonair', 'type' => 'Dragon', 'idx' => 148],
        ]);
        $data = $data->mapWithKeys(function ($pokemon) {
            return [$pokemon['name'] => $pokemon['type']];
        });
        $this->assertEquals(
            ['Blastoise' => 'Water', 'Charmander' => 'Fire', 'Dragonair' => 'Dragon'],
            $data->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapWithKeysIntegerKeys($collection)
    {
        $data = new $collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 3, 'name' => 'B'],
            ['id' => 2, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });
        $this->assertSame(
            [1, 3, 2],
            $data->keys()->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapWithKeysMultipleRows($collection)
    {
        $data = new $collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 3, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name'], $item['name'] => $item['id']];
        });
        $this->assertSame(
            [
                1 => 'A',
                'A' => 1,
                2 => 'B',
                'B' => 2,
                3 => 'C',
                'C' => 3,
            ],
            $data->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapWithKeysCallbackKey($collection)
    {
        $data = new $collection([
            3 => ['id' => 1, 'name' => 'A'],
            5 => ['id' => 3, 'name' => 'B'],
            4 => ['id' => 2, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item, $key) {
            return [$key => $item['id']];
        });
        $this->assertSame(
            [3, 5, 4],
            $data->keys()->all()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapInto($collection)
    {
        $data = new $collection([
            'first', 'second',
        ]);

        $data = $data->mapInto(TestCollectionMapIntoObject::class);

        $this->assertSame('first', $data->get(0)->value);
        $this->assertSame('second', $data->get(1)->value);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testNth($collection)
    {
        $data = new $collection([
            6 => 'a',
            4 => 'b',
            7 => 'c',
            1 => 'd',
            5 => 'e',
            3 => 'f',
        ]);

        $this->assertEquals(['a', 'e'], $data->nth(4)->all());
        $this->assertEquals(['b', 'f'], $data->nth(4, 1)->all());
        $this->assertEquals(['c'], $data->nth(4, 2)->all());
        $this->assertEquals(['d'], $data->nth(4, 3)->all());
        $this->assertEquals(['c', 'e'], $data->nth(2, 2)->all());
        $this->assertEquals(['c', 'd', 'e', 'f'], $data->nth(1, 2)->all());
        $this->assertEquals(['c', 'd', 'e', 'f'], $data->nth(1, 2)->all());
        $this->assertEquals(['e', 'f'], $data->nth(1, -2)->all());
        $this->assertEquals(['c', 'e'], $data->nth(2, -4)->all());
        $this->assertEquals(['e'], $data->nth(4, -2)->all());
        $this->assertEquals(['e'], $data->nth(2, -2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMapWithKeysOverwritingKeys($collection)
    {
        $data = new $collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
            ['id' => 1, 'name' => 'C'],
        ]);
        $data = $data->mapWithKeys(function ($item) {
            return [$item['id'] => $item['name']];
        });
        $this->assertSame(
            [
                1 => 'C',
                2 => 'B',
            ],
            $data->all()
        );
    }

    public function testTransform()
    {
        $data = new Collection(['first' => 'taylor', 'last' => 'otwell']);
        $data->transform(function ($item, $key) {
            return $key.'-'.strrev($item);
        });
        $this->assertEquals(['first' => 'first-rolyat', 'last' => 'last-llewto'], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByAttribute($collection)
    {
        $data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('rating');
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());

        $result = $data->groupBy('url');
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByAttributeWithStringableKey($collection)
    {
        $data = new $collection($payload = [
            ['name' => Str::of('Laravel'), 'url' => '1'],
            ['name' => new HtmlString('Laravel'), 'url' => '1'],
            ['name' => new class()
            {
                public function __toString()
                {
                    return 'Framework';
                }
            }, 'url' => '2', ],
        ]);

        $result = $data->groupBy('name');
        $this->assertEquals(['Laravel' => [$payload[0], $payload[1]], 'Framework' => [$payload[2]]], $result->toArray());

        $result = $data->groupBy('url');
        $this->assertEquals(['1' => [$payload[0], $payload[1]], '2' => [$payload[2]]], $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByCallable($collection)
    {
        $data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy([$this, 'sortByRating']);
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());

        $result = $data->groupBy([$this, 'sortByUrl']);
        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());
    }

    public function sortByRating(array $value)
    {
        return $value['rating'];
    }

    public function sortByUrl(array $value)
    {
        return $value['url'];
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByAttributePreservingKeys($collection)
    {
        $data = new $collection([10 => ['rating' => 1, 'url' => '1'],  20 => ['rating' => 1, 'url' => '1'],  30 => ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy('rating', true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByClosureWhereItemsHaveSingleGroup($collection)
    {
        $data = new $collection([['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1'], ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        });

        $this->assertEquals([1 => [['rating' => 1, 'url' => '1'], ['rating' => 1, 'url' => '1']], 2 => [['rating' => 2, 'url' => '2']]], $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByClosureWhereItemsHaveSingleGroupPreservingKeys($collection)
    {
        $data = new $collection([10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1'], 30 => ['rating' => 2, 'url' => '2']]);

        $result = $data->groupBy(function ($item) {
            return $item['rating'];
        }, true);

        $expected_result = [
            1 => [10 => ['rating' => 1, 'url' => '1'], 20 => ['rating' => 1, 'url' => '1']],
            2 => [30 => ['rating' => 2, 'url' => '2']],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByClosureWhereItemsHaveMultipleGroups($collection)
    {
        $data = new $collection([
            ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        });

        $expected_result = [
            'Role_1' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByClosureWhereItemsHaveMultipleGroupsPreservingKeys($collection)
    {
        $data = new $collection([
            10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            30 => ['user' => 3, 'roles' => ['Role_1']],
        ]);

        $result = $data->groupBy(function ($item) {
            return $item['roles'];
        }, true);

        $expected_result = [
            'Role_1' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
                30 => ['user' => 3, 'roles' => ['Role_1']],
            ],
            'Role_2' => [
                20 => ['user' => 2, 'roles' => ['Role_1', 'Role_2']],
            ],
            'Role_3' => [
                10 => ['user' => 1, 'roles' => ['Role_1', 'Role_3']],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGroupByMultiLevelAndClosurePreservingKeys($collection)
    {
        $data = new $collection([
            10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
            20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
            30 => ['user' => 3, 'skilllevel' => 2, 'roles' => ['Role_1']],
            40 => ['user' => 4, 'skilllevel' => 2, 'roles' => ['Role_2']],
        ]);

        $result = $data->groupBy([
            'skilllevel',
            function ($item) {
                return $item['roles'];
            },
        ], true);

        $expected_result = [
            1 => [
                'Role_1' => [
                    10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
                    20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
                ],
                'Role_3' => [
                    10 => ['user' => 1, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_3']],
                ],
                'Role_2' => [
                    20 => ['user' => 2, 'skilllevel' => 1, 'roles' => ['Role_1', 'Role_2']],
                ],
            ],
            2 => [
                'Role_1' => [
                    30 => ['user' => 3, 'skilllevel' => 2, 'roles' => ['Role_1']],
                ],
                'Role_2' => [
                    40 => ['user' => 4, 'skilllevel' => 2, 'roles' => ['Role_2']],
                ],
            ],
        ];

        $this->assertEquals($expected_result, $result->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testKeyByAttribute($collection)
    {
        $data = new $collection([['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']]);

        $result = $data->keyBy('rating');
        $this->assertEquals([1 => ['rating' => 1, 'name' => '1'], 2 => ['rating' => 2, 'name' => '2'], 3 => ['rating' => 3, 'name' => '3']], $result->all());

        $result = $data->keyBy(function ($item) {
            return $item['rating'] * 2;
        });
        $this->assertEquals([2 => ['rating' => 1, 'name' => '1'], 4 => ['rating' => 2, 'name' => '2'], 6 => ['rating' => 3, 'name' => '3']], $result->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testKeyByClosure($collection)
    {
        $data = new $collection([
            ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ]);
        $result = $data->keyBy(function ($item, $key) {
            return strtolower($key.'-'.$item['firstname'].$item['lastname']);
        });
        $this->assertEquals([
            '0-taylorotwell' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            '1-lucasmichot' => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ], $result->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testKeyByObject($collection)
    {
        $data = new $collection([
            ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ]);
        $result = $data->keyBy(function ($item, $key) use ($collection) {
            return new $collection([$key, $item['firstname'], $item['lastname']]);
        });
        $this->assertEquals([
            '[0,"Taylor","Otwell"]' => ['firstname' => 'Taylor', 'lastname' => 'Otwell', 'locale' => 'US'],
            '[1,"Lucas","Michot"]' => ['firstname' => 'Lucas', 'lastname' => 'Michot', 'locale' => 'FR'],
        ], $result->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testContains($collection)
    {
        $c = new $collection([1, 3, 5]);

        $this->assertTrue($c->contains(1));
        $this->assertTrue($c->contains('1'));
        $this->assertFalse($c->contains(2));
        $this->assertFalse($c->contains('2'));

        $c = new $collection(['1']);
        $this->assertTrue($c->contains('1'));
        $this->assertTrue($c->contains(1));

        $c = new $collection([null]);
        $this->assertTrue($c->contains(false));
        $this->assertTrue($c->contains(null));
        $this->assertTrue($c->contains([]));
        $this->assertTrue($c->contains(0));
        $this->assertTrue($c->contains(''));

        $c = new $collection([0]);
        $this->assertTrue($c->contains(0));
        $this->assertTrue($c->contains('0'));
        $this->assertTrue($c->contains(false));
        $this->assertTrue($c->contains(null));

        $this->assertTrue($c->contains(function ($value) {
            return $value < 5;
        }));
        $this->assertFalse($c->contains(function ($value) {
            return $value > 5;
        }));

        $c = new $collection([['v' => 1], ['v' => 3], ['v' => 5]]);

        $this->assertTrue($c->contains('v', 1));
        $this->assertFalse($c->contains('v', 2));

        $c = new $collection(['date', 'class', (object) ['foo' => 50]]);

        $this->assertTrue($c->contains('date'));
        $this->assertTrue($c->contains('class'));
        $this->assertFalse($c->contains('foo'));

        $c = new $collection([['a' => false, 'b' => false], ['a' => true, 'b' => false]]);

        $this->assertTrue($c->contains->a);
        $this->assertFalse($c->contains->b);

        $c = new $collection([
            null, 1, 2,
        ]);

        $this->assertTrue($c->contains(function ($value) {
            return is_null($value);
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDoesntContain($collection)
    {
        $c = new $collection([1, 3, 5]);

        $this->assertFalse($c->doesntContain(1));
        $this->assertFalse($c->doesntContain('1'));
        $this->assertTrue($c->doesntContain(2));
        $this->assertTrue($c->doesntContain('2'));

        $c = new $collection(['1']);
        $this->assertFalse($c->doesntContain('1'));
        $this->assertFalse($c->doesntContain(1));

        $c = new $collection([null]);
        $this->assertFalse($c->doesntContain(false));
        $this->assertFalse($c->doesntContain(null));
        $this->assertFalse($c->doesntContain([]));
        $this->assertFalse($c->doesntContain(0));
        $this->assertFalse($c->doesntContain(''));

        $c = new $collection([0]);
        $this->assertFalse($c->doesntContain(0));
        $this->assertFalse($c->doesntContain('0'));
        $this->assertFalse($c->doesntContain(false));
        $this->assertFalse($c->doesntContain(null));

        $this->assertFalse($c->doesntContain(function ($value) {
            return $value < 5;
        }));
        $this->assertTrue($c->doesntContain(function ($value) {
            return $value > 5;
        }));

        $c = new $collection([['v' => 1], ['v' => 3], ['v' => 5]]);

        $this->assertFalse($c->doesntContain('v', 1));
        $this->assertTrue($c->doesntContain('v', 2));

        $c = new $collection(['date', 'class', (object) ['foo' => 50]]);

        $this->assertFalse($c->doesntContain('date'));
        $this->assertFalse($c->doesntContain('class'));
        $this->assertTrue($c->doesntContain('foo'));

        $c = new $collection([['a' => false, 'b' => false], ['a' => true, 'b' => false]]);

        $this->assertFalse($c->doesntContain->a);
        $this->assertTrue($c->doesntContain->b);

        $c = new $collection([
            null, 1, 2,
        ]);

        $this->assertFalse($c->doesntContain(function ($value) {
            return is_null($value);
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSome($collection)
    {
        $c = new $collection([1, 3, 5]);

        $this->assertTrue($c->some(1));
        $this->assertFalse($c->some(2));
        $this->assertTrue($c->some(function ($value) {
            return $value < 5;
        }));
        $this->assertFalse($c->some(function ($value) {
            return $value > 5;
        }));

        $c = new $collection([['v' => 1], ['v' => 3], ['v' => 5]]);

        $this->assertTrue($c->some('v', 1));
        $this->assertFalse($c->some('v', 2));

        $c = new $collection(['date', 'class', (object) ['foo' => 50]]);

        $this->assertTrue($c->some('date'));
        $this->assertTrue($c->some('class'));
        $this->assertFalse($c->some('foo'));

        $c = new $collection([['a' => false, 'b' => false], ['a' => true, 'b' => false]]);

        $this->assertTrue($c->some->a);
        $this->assertFalse($c->some->b);

        $c = new $collection([
            null, 1, 2,
        ]);

        $this->assertTrue($c->some(function ($value) {
            return is_null($value);
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testContainsStrict($collection)
    {
        $c = new $collection([1, 3, 5, '02']);

        $this->assertTrue($c->containsStrict(1));
        $this->assertFalse($c->containsStrict('1'));
        $this->assertFalse($c->containsStrict(2));
        $this->assertTrue($c->containsStrict('02'));
        $this->assertFalse($c->containsStrict(true));
        $this->assertTrue($c->containsStrict(function ($value) {
            return $value < 5;
        }));
        $this->assertFalse($c->containsStrict(function ($value) {
            return $value > 5;
        }));

        $c = new $collection([0]);
        $this->assertTrue($c->containsStrict(0));
        $this->assertFalse($c->containsStrict('0'));

        $this->assertFalse($c->containsStrict(false));
        $this->assertFalse($c->containsStrict(null));

        $c = new $collection([1, null]);
        $this->assertTrue($c->containsStrict(null));
        $this->assertFalse($c->containsStrict(0));
        $this->assertFalse($c->containsStrict(false));

        $c = new $collection([['v' => 1], ['v' => 3], ['v' => '04'], ['v' => 5]]);

        $this->assertTrue($c->containsStrict('v', 1));
        $this->assertFalse($c->containsStrict('v', 2));
        $this->assertFalse($c->containsStrict('v', '1'));
        $this->assertFalse($c->containsStrict('v', 4));
        $this->assertTrue($c->containsStrict('v', '04'));

        $c = new $collection(['date', 'class', (object) ['foo' => 50], '']);

        $this->assertTrue($c->containsStrict('date'));
        $this->assertTrue($c->containsStrict('class'));
        $this->assertFalse($c->containsStrict('foo'));
        $this->assertFalse($c->containsStrict(null));
        $this->assertTrue($c->containsStrict(''));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testContainsWithOperator($collection)
    {
        $c = new $collection([['v' => 1], ['v' => 3], ['v' => '4'], ['v' => 5]]);

        $this->assertTrue($c->contains('v', '=', 4));
        $this->assertTrue($c->contains('v', '==', 4));
        $this->assertFalse($c->contains('v', '===', 4));
        $this->assertTrue($c->contains('v', '>', 4));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGettingSumFromCollection($collection)
    {
        $c = new $collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
        $this->assertEquals(100, $c->sum('foo'));

        $c = new $collection([(object) ['foo' => 50], (object) ['foo' => 50]]);
        $this->assertEquals(100, $c->sum(function ($i) {
            return $i->foo;
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCanSumValuesWithoutACallback($collection)
    {
        $c = new $collection([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $c->sum());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGettingSumFromEmptyCollection($collection)
    {
        $c = new $collection;
        $this->assertEquals(0, $c->sum('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testValueRetrieverAcceptsDotNotation($collection)
    {
        $c = new $collection([
            (object) ['id' => 1, 'foo' => ['bar' => 'B']], (object) ['id' => 2, 'foo' => ['bar' => 'A']],
        ]);

        $c = $c->sortBy('foo.bar');
        $this->assertEquals([2, 1], $c->pluck('id')->all());
    }

    public function testPullRetrievesItemFromCollection()
    {
        $c = new Collection(['foo', 'bar']);

        $this->assertSame('foo', $c->pull(0));
        $this->assertSame('bar', $c->pull(1));

        $c = new Collection(['foo', 'bar']);

        $this->assertNull($c->pull(-1));
        $this->assertNull($c->pull(2));
    }

    public function testPullRemovesItemFromCollection()
    {
        $c = new Collection(['foo', 'bar']);
        $c->pull(0);
        $this->assertEquals([1 => 'bar'], $c->all());
        $c->pull(1);
        $this->assertEquals([], $c->all());
    }

    public function testPullRemovesItemFromNestedCollection()
    {
        $nestedCollection = new Collection([
            new Collection([
                'value',
                new Collection([
                    'bar' => 'baz',
                    'test' => 'value',
                ]),
            ]),
            'bar',
        ]);

        $nestedCollection->pull('0.1.test');

        $actualArray = $nestedCollection->toArray();
        $expectedArray = [
            [
                'value',
                ['bar' => 'baz'],
            ],
            'bar',
        ];

        $this->assertEquals($expectedArray, $actualArray);
    }

    public function testPullReturnsDefault()
    {
        $c = new Collection([]);
        $value = $c->pull(0, 'foo');
        $this->assertSame('foo', $value);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRejectRemovesElementsPassingTruthTest($collection)
    {
        $c = new $collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject('bar')->values()->all());

        $c = new $collection(['foo', 'bar']);
        $this->assertEquals(['foo'], $c->reject(function ($v) {
            return $v === 'bar';
        })->values()->all());

        $c = new $collection(['foo', null]);
        $this->assertEquals(['foo'], $c->reject(null)->values()->all());

        $c = new $collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject('baz')->values()->all());

        $c = new $collection(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $c->reject(function ($v) {
            return $v === 'baz';
        })->values()->all());

        $c = new $collection(['id' => 1, 'primary' => 'foo', 'secondary' => 'bar']);
        $this->assertEquals(['primary' => 'foo', 'secondary' => 'bar'], $c->reject(function ($item, $key) {
            return $key === 'id';
        })->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRejectWithoutAnArgumentRemovesTruthyValues($collection)
    {
        $data1 = new $collection([
            false,
            true,
            new $collection(),
            0,
        ]);
        $this->assertSame([0 => false, 3 => 0], $data1->reject()->all());

        $data2 = new $collection([
            'a' => true,
            'b' => true,
            'c' => true,
        ]);
        $this->assertTrue(
            $data2->reject()->isEmpty()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSearchReturnsIndexOfFirstFoundItem($collection)
    {
        $c = new $collection([1, 2, 3, 4, 5, 2, 5, 'foo' => 'bar']);

        $this->assertEquals(1, $c->search(2));
        $this->assertEquals(1, $c->search('2'));
        $this->assertSame('foo', $c->search('bar'));
        $this->assertEquals(4, $c->search(function ($value) {
            return $value > 4;
        }));
        $this->assertSame('foo', $c->search(function ($value) {
            return ! is_numeric($value);
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSearchInStrictMode($collection)
    {
        $c = new $collection([false, 0, 1, [], '']);
        $this->assertFalse($c->search('false', true));
        $this->assertFalse($c->search('1', true));
        $this->assertEquals(0, $c->search(false, true));
        $this->assertEquals(1, $c->search(0, true));
        $this->assertEquals(2, $c->search(1, true));
        $this->assertEquals(3, $c->search([], true));
        $this->assertEquals(4, $c->search('', true));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSearchReturnsFalseWhenItemIsNotFound($collection)
    {
        $c = new $collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

        $this->assertFalse($c->search(6));
        $this->assertFalse($c->search('foo'));
        $this->assertFalse($c->search(function ($value) {
            return $value < 1 && is_numeric($value);
        }));
        $this->assertFalse($c->search(function ($value) {
            return $value === 'nope';
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testKeys($collection)
    {
        $c = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals(['name', 'framework'], $c->keys()->all());

        $c = new $collection(['taylor', 'laravel']);
        $this->assertEquals([0, 1], $c->keys()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPaginate($collection)
    {
        $c = new $collection(['one', 'two', 'three', 'four']);
        $this->assertEquals(['one', 'two'], $c->forPage(0, 2)->all());
        $this->assertEquals(['one', 'two'], $c->forPage(1, 2)->all());
        $this->assertEquals([2 => 'three', 3 => 'four'], $c->forPage(2, 2)->all());
        $this->assertEquals([], $c->forPage(3, 2)->all());
    }

    public function testPrepend()
    {
        $c = new Collection(['one', 'two', 'three', 'four']);
        $this->assertEquals(
            ['zero', 'one', 'two', 'three', 'four'],
            $c->prepend('zero')->all()
        );

        $c = new Collection(['one' => 1, 'two' => 2]);
        $this->assertEquals(
            ['zero' => 0, 'one' => 1, 'two' => 2],
            $c->prepend(0, 'zero')->all()
        );

        $c = new Collection(['one' => 1, 'two' => 2]);
        $this->assertEquals(
            [null => 0, 'one' => 1, 'two' => 2],
            $c->prepend(0, null)->all()
        );
    }

    public function testPushWithOneItem()
    {
        $expected = [
            0 => 4,
            1 => 5,
            2 => 6,
            3 => ['a', 'b', 'c'],
            4 => ['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe'],
            5 => 'Jonny from Laroe',
        ];

        $data = new Collection([4, 5, 6]);
        $data->push(['a', 'b', 'c']);
        $data->push(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
        $actual = $data->push('Jonny from Laroe')->toArray();

        $this->assertSame($expected, $actual);
    }

    public function testPushWithMultipleItems()
    {
        $expected = [
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 'Jonny',
            4 => 'from',
            5 => 'Laroe',
            6 => 'Jonny',
            7 => 'from',
            8 => 'Laroe',
            9 => 'a',
            10 => 'b',
            11 => 'c',
        ];

        $data = new Collection([4, 5, 6]);
        $data->push('Jonny', 'from', 'Laroe');
        $data->push(...[11 => 'Jonny', 12 => 'from', 13 => 'Laroe']);
        $data->push(...collect(['a', 'b', 'c']));
        $actual = $data->push(...[])->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testZip($collection)
    {
        $c = new $collection([1, 2, 3]);
        $c = $c->zip(new $collection([4, 5, 6]));
        $this->assertInstanceOf($collection, $c);
        $this->assertInstanceOf($collection, $c->get(0));
        $this->assertInstanceOf($collection, $c->get(1));
        $this->assertInstanceOf($collection, $c->get(2));
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4], $c->get(0)->all());
        $this->assertEquals([2, 5], $c->get(1)->all());
        $this->assertEquals([3, 6], $c->get(2)->all());

        $c = new $collection([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7, 8, 9]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c->get(0)->all());
        $this->assertEquals([2, 5, 8], $c->get(1)->all());
        $this->assertEquals([3, 6, 9], $c->get(2)->all());

        $c = new $collection([1, 2, 3]);
        $c = $c->zip([4, 5, 6], [7]);
        $this->assertCount(3, $c);
        $this->assertEquals([1, 4, 7], $c->get(0)->all());
        $this->assertEquals([2, 5, null], $c->get(1)->all());
        $this->assertEquals([3, 6, null], $c->get(2)->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPadPadsArrayWithValue($collection)
    {
        $c = new $collection([1, 2, 3]);
        $c = $c->pad(4, 0);
        $this->assertEquals([1, 2, 3, 0], $c->all());

        $c = new $collection([1, 2, 3, 4, 5]);
        $c = $c->pad(4, 0);
        $this->assertEquals([1, 2, 3, 4, 5], $c->all());

        $c = new $collection([1, 2, 3]);
        $c = $c->pad(-4, 0);
        $this->assertEquals([0, 1, 2, 3], $c->all());

        $c = new $collection([1, 2, 3, 4, 5]);
        $c = $c->pad(-4, 0);
        $this->assertEquals([1, 2, 3, 4, 5], $c->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGettingMaxItemsFromCollection($collection)
    {
        $c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(20, $c->max(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(20, $c->max('foo'));
        $this->assertEquals(20, $c->max->foo);

        $c = new $collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(20, $c->max('foo'));
        $this->assertEquals(20, $c->max->foo);

        $c = new $collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $c->max());

        $c = new $collection;
        $this->assertNull($c->max());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGettingMinItemsFromCollection($collection)
    {
        $c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(10, $c->min(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(10, $c->min('foo'));
        $this->assertEquals(10, $c->min->foo);

        $c = new $collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(10, $c->min('foo'));
        $this->assertEquals(10, $c->min->foo);

        $c = new $collection([['foo' => 10], ['foo' => 20], ['foo' => null]]);
        $this->assertEquals(10, $c->min('foo'));
        $this->assertEquals(10, $c->min->foo);

        $c = new $collection([1, 2, 3, 4, 5]);
        $this->assertEquals(1, $c->min());

        $c = new $collection([1, null, 3, 4, 5]);
        $this->assertEquals(1, $c->min());

        $c = new $collection([0, 1, 2, 3, 4]);
        $this->assertEquals(0, $c->min());

        $c = new $collection;
        $this->assertNull($c->min());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testOnly($collection)
    {
        $data = new $collection(['first' => 'Taylor', 'last' => 'Otwell', 'email' => 'taylorotwell@gmail.com']);

        $this->assertEquals($data->all(), $data->only(null)->all());
        $this->assertEquals(['first' => 'Taylor'], $data->only(['first', 'missing'])->all());
        $this->assertEquals(['first' => 'Taylor'], $data->only('first', 'missing')->all());
        $this->assertEquals(['first' => 'Taylor'], $data->only(collect(['first', 'missing']))->all());

        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->only(['first', 'email'])->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->only('first', 'email')->all());
        $this->assertEquals(['first' => 'Taylor', 'email' => 'taylorotwell@gmail.com'], $data->only(collect(['first', 'email']))->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGettingAvgItemsFromCollection($collection)
    {
        $c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
        $this->assertEquals(15, $c->avg(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(15, $c->avg('foo'));
        $this->assertEquals(15, $c->avg->foo);

        $c = new $collection([(object) ['foo' => 10], (object) ['foo' => 20], (object) ['foo' => null]]);
        $this->assertEquals(15, $c->avg(function ($item) {
            return $item->foo;
        }));
        $this->assertEquals(15, $c->avg('foo'));
        $this->assertEquals(15, $c->avg->foo);

        $c = new $collection([['foo' => 10], ['foo' => 20]]);
        $this->assertEquals(15, $c->avg('foo'));
        $this->assertEquals(15, $c->avg->foo);

        $c = new $collection([1, 2, 3, 4, 5]);
        $this->assertEquals(3, $c->avg());

        $c = new $collection;
        $this->assertNull($c->avg());

        $c = new $collection([['foo' => '4'], ['foo' => '2']]);
        $this->assertIsInt($c->avg('foo'));
        $this->assertEquals(3, $c->avg('foo'));

        $c = new $collection([['foo' => 1], ['foo' => 2]]);
        $this->assertIsFloat($c->avg('foo'));
        $this->assertEquals(1.5, $c->avg('foo'));

        $c = new $collection([
            ['foo' => 1], ['foo' => 2],
            (object) ['foo' => 6],
        ]);
        $this->assertEquals(3, $c->avg('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testJsonSerialize($collection)
    {
        $c = new $collection([
            new TestArrayableObject,
            new TestJsonableObject,
            new TestJsonSerializeObject,
            new TestJsonSerializeToStringObject,
            'baz',
        ]);

        $this->assertSame([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            'foobar',
            'baz',
        ], $c->jsonSerialize());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCombineWithArray($collection)
    {
        $c = new $collection([1, 2, 3]);
        $actual = $c->combine([4, 5, 6])->toArray();
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $this->assertSame($expected, $actual);

        $c = new $collection(['name', 'family']);
        $actual = $c->combine([1 => 'taylor', 2 => 'otwell'])->toArray();
        $expected = [
            'name' => 'taylor',
            'family' => 'otwell',
        ];

        $this->assertSame($expected, $actual);

        $c = new $collection([1 => 'name', 2 => 'family']);
        $actual = $c->combine(['taylor', 'otwell'])->toArray();
        $expected = [
            'name' => 'taylor',
            'family' => 'otwell',
        ];

        $this->assertSame($expected, $actual);

        $c = new $collection([1 => 'name', 2 => 'family']);
        $actual = $c->combine([2 => 'taylor', 3 => 'otwell'])->toArray();
        $expected = [
            'name' => 'taylor',
            'family' => 'otwell',
        ];

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCombineWithCollection($collection)
    {
        $expected = [
            1 => 4,
            2 => 5,
            3 => 6,
        ];

        $keyCollection = new $collection(array_keys($expected));
        $valueCollection = new $collection(array_values($expected));
        $actual = $keyCollection->combine($valueCollection)->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConcatWithArray($collection)
    {
        $expected = [
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 'a',
            4 => 'b',
            5 => 'c',
            6 => 'Jonny',
            7 => 'from',
            8 => 'Laroe',
            9 => 'Jonny',
            10 => 'from',
            11 => 'Laroe',
        ];

        $data = new $collection([4, 5, 6]);
        $data = $data->concat(['a', 'b', 'c']);
        $data = $data->concat(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
        $actual = $data->concat(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe'])->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testConcatWithCollection($collection)
    {
        $expected = [
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 'a',
            4 => 'b',
            5 => 'c',
            6 => 'Jonny',
            7 => 'from',
            8 => 'Laroe',
            9 => 'Jonny',
            10 => 'from',
            11 => 'Laroe',
        ];

        $firstCollection = new $collection([4, 5, 6]);
        $secondCollection = new $collection(['a', 'b', 'c']);
        $thirdCollection = new $collection(['who' => 'Jonny', 'preposition' => 'from', 'where' => 'Laroe']);
        $firstCollection = $firstCollection->concat($secondCollection);
        $firstCollection = $firstCollection->concat($thirdCollection);
        $actual = $firstCollection->concat($thirdCollection)->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testDump($collection)
    {
        $log = new Collection;

        VarDumper::setHandler(function ($value) use ($log) {
            $log->add($value);
        });

        (new $collection([1, 2, 3]))->dump('one', 'two');

        $this->assertSame(['one', 'two', [1, 2, 3]], $log->all());

        VarDumper::setHandler(null);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReduce($collection)
    {
        $data = new $collection([1, 2, 3]);
        $this->assertEquals(6, $data->reduce(function ($carry, $element) {
            return $carry += $element;
        }));

        $data = new $collection([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
        $this->assertSame('foobarbazqux', $data->reduce(function ($carry, $element, $key) {
            return $carry .= $key.$element;
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReduceSpread($collection)
    {
        $data = new $collection([-1, 0, 1, 2, 3, 4, 5]);

        [$sum, $max, $min] = $data->reduceSpread(function ($sum, $max, $min, $value) {
            $sum += $value;
            $max = max($max, $value);
            $min = min($min, $value);

            return [$sum, $max, $min];
        }, 0, PHP_INT_MIN, PHP_INT_MAX);

        $this->assertEquals(14, $sum);
        $this->assertEquals(5, $max);
        $this->assertEquals(-1, $min);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testReduceSpreadThrowsAnExceptionIfReducerDoesNotReturnAnArray($collection)
    {
        $data = new $collection([1]);

        $this->expectException(UnexpectedValueException::class);

        $data->reduceSpread(function () {
            return false;
        }, null);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testRandomThrowsAnExceptionUsingAmountBiggerThanCollectionSize($collection)
    {
        $this->expectException(InvalidArgumentException::class);

        $data = new $collection([1, 2, 3]);
        $data->random(4);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPipe($collection)
    {
        $data = new $collection([1, 2, 3]);

        $this->assertEquals(6, $data->pipe(function ($data) {
            return $data->sum();
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPipeInto($collection)
    {
        $data = new $collection([
            'first', 'second',
        ]);

        $instance = $data->pipeInto(TestCollectionMapIntoObject::class);

        $this->assertSame($data, $instance->value);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPipeThrough($collection)
    {
        $data = new $collection([1, 2, 3]);

        $result = $data->pipeThrough([
            function ($data) {
                return $data->merge([4, 5]);
            },
            function ($data) {
                return $data->sum();
            },
        ]);

        $this->assertEquals(15, $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMedianValueWithArrayCollection($collection)
    {
        $data = new $collection([1, 2, 2, 4]);

        $this->assertEquals(2, $data->median());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMedianValueByKey($collection)
    {
        $data = new $collection([
            (object) ['foo' => 1],
            (object) ['foo' => 2],
            (object) ['foo' => 2],
            (object) ['foo' => 4],
        ]);
        $this->assertEquals(2, $data->median('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMedianOnCollectionWithNull($collection)
    {
        $data = new $collection([
            (object) ['foo' => 1],
            (object) ['foo' => 2],
            (object) ['foo' => 4],
            (object) ['foo' => null],
        ]);
        $this->assertEquals(2, $data->median('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testEvenMedianCollection($collection)
    {
        $data = new $collection([
            (object) ['foo' => 0],
            (object) ['foo' => 3],
        ]);
        $this->assertEquals(1.5, $data->median('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMedianOutOfOrderCollection($collection)
    {
        $data = new $collection([
            (object) ['foo' => 0],
            (object) ['foo' => 5],
            (object) ['foo' => 3],
        ]);
        $this->assertEquals(3, $data->median('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMedianOnEmptyCollectionReturnsNull($collection)
    {
        $data = new $collection;
        $this->assertNull($data->median());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testModeOnNullCollection($collection)
    {
        $data = new $collection;
        $this->assertNull($data->mode());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testMode($collection)
    {
        $data = new $collection([1, 2, 3, 4, 4, 5]);
        $this->assertIsArray($data->mode());
        $this->assertEquals([4], $data->mode());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testModeValueByKey($collection)
    {
        $data = new $collection([
            (object) ['foo' => 1],
            (object) ['foo' => 1],
            (object) ['foo' => 2],
            (object) ['foo' => 4],
        ]);
        $data2 = new Collection([
            ['foo' => 1],
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 4],
        ]);
        $this->assertEquals([1], $data->mode('foo'));
        $this->assertEquals($data2->mode('foo'), $data->mode('foo'));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWithMultipleModeValues($collection)
    {
        $data = new $collection([1, 2, 2, 1]);
        $this->assertEquals([1, 2], $data->mode());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceOffset($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6, 7, 8], $data->slice(3)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceNegativeOffset($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([6, 7, 8], $data->slice(-3)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceOffsetAndLength($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6], $data->slice(3, 3)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceOffsetAndNegativeLength($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6, 7], $data->slice(3, -1)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceNegativeOffsetAndLength($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([4, 5, 6], $data->slice(-5, 3)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSliceNegativeOffsetAndNegativeLength($collection)
    {
        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8]);
        $this->assertEquals([3, 4, 5, 6], $data->slice(-6, -2)->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollectionFromTraversable($collection)
    {
        $data = new $collection(new ArrayObject([1, 2, 3]));
        $this->assertEquals([1, 2, 3], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollectionFromTraversableWithKeys($collection)
    {
        $data = new $collection(new ArrayObject(['foo' => 1, 'bar' => 2, 'baz' => 3]));
        $this->assertEquals(['foo' => 1, 'bar' => 2, 'baz' => 3], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     *
     * @requires PHP >= 8.1
     */
    public function testCollectionFromEnum($collection)
    {
        $data = new $collection(TestEnum::A);
        $this->assertEquals([TestEnum::A], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     *
     * @requires PHP >= 8.1
     */
    public function testCollectionFromBackedEnum($collection)
    {
        $data = new $collection(TestBackedEnum::A);
        $this->assertEquals([TestBackedEnum::A], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionWithADivisableCount($collection)
    {
        $data = new $collection(['a', 'b', 'c', 'd']);
        $split = $data->split(2);

        $this->assertSame(['a', 'b'], $split->get(0)->all());
        $this->assertSame(['c', 'd'], $split->get(1)->all());
        $this->assertInstanceOf($collection, $split);

        $this->assertEquals(
            [['a', 'b'], ['c', 'd']],
            $data->split(2)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );

        $data = new $collection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
        $split = $data->split(2);

        $this->assertSame([1, 2, 3, 4, 5], $split->get(0)->all());
        $this->assertSame([6, 7, 8, 9, 10], $split->get(1)->all());

        $this->assertEquals(
            [[1, 2, 3, 4, 5], [6, 7, 8, 9, 10]],
            $data->split(2)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionWithAnUndivisableCount($collection)
    {
        $data = new $collection(['a', 'b', 'c']);
        $split = $data->split(2);

        $this->assertSame(['a', 'b'], $split->get(0)->all());
        $this->assertSame(['c'], $split->get(1)->all());

        $this->assertEquals(
            [['a', 'b'], ['c']],
            $data->split(2)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionWithCountLessThenDivisor($collection)
    {
        $data = new $collection(['a']);
        $split = $data->split(2);

        $this->assertSame(['a'], $split->get(0)->all());
        $this->assertNull($split->get(1));

        $this->assertEquals(
            [['a']],
            $data->split(2)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionIntoThreeWithCountOfFour($collection)
    {
        $data = new $collection(['a', 'b', 'c', 'd']);
        $split = $data->split(3);

        $this->assertSame(['a', 'b'], $split->get(0)->all());
        $this->assertSame(['c'], $split->get(1)->all());
        $this->assertSame(['d'], $split->get(2)->all());

        $this->assertEquals(
            [['a', 'b'], ['c'], ['d']],
            $data->split(3)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionIntoThreeWithCountOfFive($collection)
    {
        $data = new $collection(['a', 'b', 'c', 'd', 'e']);
        $split = $data->split(3);

        $this->assertSame(['a', 'b'], $split->get(0)->all());
        $this->assertSame(['c', 'd'], $split->get(1)->all());
        $this->assertSame(['e'], $split->get(2)->all());

        $this->assertEquals(
            [['a', 'b'], ['c', 'd'], ['e']],
            $data->split(3)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitCollectionIntoSixWithCountOfTen($collection)
    {
        $data = new $collection(['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j']);
        $split = $data->split(6);

        $this->assertSame(['a', 'b'], $split->get(0)->all());
        $this->assertSame(['c', 'd'], $split->get(1)->all());
        $this->assertSame(['e', 'f'], $split->get(2)->all());
        $this->assertSame(['g', 'h'], $split->get(3)->all());
        $this->assertSame(['i'], $split->get(4)->all());
        $this->assertSame(['j'], $split->get(5)->all());

        $this->assertEquals(
            [['a', 'b'], ['c', 'd'], ['e', 'f'], ['g', 'h'], ['i'], ['j']],
            $data->split(6)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testSplitEmptyCollection($collection)
    {
        $data = new $collection;
        $split = $data->split(2);

        $this->assertNull($split->get(0));
        $this->assertNull($split->get(1));

        $this->assertEquals(
            [],
            $data->split(2)->map(function (Collection $chunk) {
                return $chunk->values()->toArray();
            })->toArray()
        );
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderCollectionGroupBy($collection)
    {
        $data = new $collection([
            new TestSupportCollectionHigherOrderItem,
            new TestSupportCollectionHigherOrderItem('TAYLOR'),
            new TestSupportCollectionHigherOrderItem('foo'),
        ]);

        $this->assertEquals([
            'taylor' => [$data->get(0)],
            'TAYLOR' => [$data->get(1)],
            'foo' => [$data->get(2)],
        ], $data->groupBy->name->toArray());

        $this->assertEquals([
            'TAYLOR' => [$data->get(0), $data->get(1)],
            'FOO' => [$data->get(2)],
        ], $data->groupBy->uppercase()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderCollectionMap($collection)
    {
        $person1 = (object) ['name' => 'Taylor'];
        $person2 = (object) ['name' => 'Yaz'];

        $data = new $collection([$person1, $person2]);

        $this->assertEquals(['Taylor', 'Yaz'], $data->map->name->toArray());

        $data = new $collection([new TestSupportCollectionHigherOrderItem, new TestSupportCollectionHigherOrderItem]);

        $this->assertEquals(['TAYLOR', 'TAYLOR'], $data->each->uppercase()->map->name->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderCollectionMapFromArrays($collection)
    {
        $person1 = ['name' => 'Taylor'];
        $person2 = ['name' => 'Yaz'];

        $data = new $collection([$person1, $person2]);

        $this->assertEquals(['Taylor', 'Yaz'], $data->map->name->toArray());

        $data = new $collection([new TestSupportCollectionHigherOrderItem, new TestSupportCollectionHigherOrderItem]);

        $this->assertEquals(['TAYLOR', 'TAYLOR'], $data->each->uppercase()->map->name->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartition($collection)
    {
        $data = new $collection(range(1, 10));

        [$firstPartition, $secondPartition] = $data->partition(function ($i) {
            return $i <= 5;
        })->all();

        $this->assertEquals([1, 2, 3, 4, 5], $firstPartition->values()->toArray());
        $this->assertEquals([6, 7, 8, 9, 10], $secondPartition->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartitionCallbackWithKey($collection)
    {
        $data = new $collection(['zero', 'one', 'two', 'three']);

        [$even, $odd] = $data->partition(function ($item, $index) {
            return $index % 2 === 0;
        })->all();

        $this->assertEquals(['zero', 'two'], $even->values()->toArray());
        $this->assertEquals(['one', 'three'], $odd->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartitionByKey($collection)
    {
        $courses = new $collection([
            ['free' => true, 'title' => 'Basic'], ['free' => false, 'title' => 'Premium'],
        ]);

        [$free, $premium] = $courses->partition('free')->all();

        $this->assertSame([['free' => true, 'title' => 'Basic']], $free->values()->toArray());
        $this->assertSame([['free' => false, 'title' => 'Premium']], $premium->values()->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartitionWithOperators($collection)
    {
        $data = new $collection([
            ['name' => 'Tim', 'age' => 17],
            ['name' => 'Agatha', 'age' => 62],
            ['name' => 'Kristina', 'age' => 33],
            ['name' => 'Tim', 'age' => 41],
        ]);

        [$tims, $others] = $data->partition('name', 'Tim')->all();

        $this->assertEquals([
            ['name' => 'Tim', 'age' => 17],
            ['name' => 'Tim', 'age' => 41],
        ], $tims->values()->all());

        $this->assertEquals([
            ['name' => 'Agatha', 'age' => 62],
            ['name' => 'Kristina', 'age' => 33],
        ], $others->values()->all());

        [$adults, $minors] = $data->partition('age', '>=', 18)->all();

        $this->assertEquals([
            ['name' => 'Agatha', 'age' => 62],
            ['name' => 'Kristina', 'age' => 33],
            ['name' => 'Tim', 'age' => 41],
        ], $adults->values()->all());

        $this->assertEquals([
            ['name' => 'Tim', 'age' => 17],
        ], $minors->values()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartitionPreservesKeys($collection)
    {
        $courses = new $collection([
            'a' => ['free' => true], 'b' => ['free' => false], 'c' => ['free' => true],
        ]);

        [$free, $premium] = $courses->partition('free')->all();

        $this->assertSame(['a' => ['free' => true], 'c' => ['free' => true]], $free->toArray());
        $this->assertSame(['b' => ['free' => false]], $premium->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testPartitionEmptyCollection($collection)
    {
        $data = new $collection;

        $this->assertCount(2, $data->partition(function () {
            return true;
        }));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderPartition($collection)
    {
        $courses = new $collection([
            'a' => ['free' => true], 'b' => ['free' => false], 'c' => ['free' => true],
        ]);

        [$free, $premium] = $courses->partition->free->all();

        $this->assertSame(['a' => ['free' => true], 'c' => ['free' => true]], $free->toArray());

        $this->assertSame(['b' => ['free' => false]], $premium->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testTap($collection)
    {
        $data = new $collection([1, 2, 3]);

        $fromTap = [];
        $tappedInstance = null;
        $data = $data->tap(function ($data) use (&$fromTap, &$tappedInstance) {
            $fromTap = $data->slice(0, 1)->toArray();
            $tappedInstance = $data;
        });

        $this->assertSame($data, $tappedInstance);
        $this->assertSame([1], $fromTap);
        $this->assertSame([1, 2, 3], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhen($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->when('adam', function ($data, $newName) {
            return $data->concat([$newName]);
        });

        $this->assertSame(['michael', 'tom', 'adam'], $data->toArray());

        $data = new $collection(['michael', 'tom']);

        $data = $data->when(false, function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['michael', 'tom'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhenDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->when(false, function ($data) {
            return $data->concat(['adam']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'taylor'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhenEmpty($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->whenEmpty(function () {
            throw new Exception('whenEmpty() should not trigger on a collection with items');
        });

        $this->assertSame(['michael', 'tom'], $data->toArray());

        $data = new $collection;

        $data = $data->whenEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhenEmptyDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->whenEmpty(function ($data) {
            return $data->concat(['adam']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'taylor'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhenNotEmpty($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->whenNotEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['michael', 'tom', 'adam'], $data->toArray());

        $data = new $collection;

        $data = $data->whenNotEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame([], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhenNotEmptyDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->whenNotEmpty(function ($data) {
            return $data->concat(['adam']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderWhenAndUnless($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->when(true)->concat(['chris']);

        $this->assertSame(['michael', 'tom', 'chris'], $data->toArray());

        $data = $data->when(false)->concat(['adam']);

        $this->assertSame(['michael', 'tom', 'chris'], $data->toArray());

        $data = $data->unless(false)->concat(['adam']);

        $this->assertSame(['michael', 'tom', 'chris', 'adam'], $data->toArray());

        $data = $data->unless(true)->concat(['bogdan']);

        $this->assertSame(['michael', 'tom', 'chris', 'adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHigherOrderWhenAndUnlessWithProxy($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->when->contains('michael')->concat(['chris']);

        $this->assertSame(['michael', 'tom', 'chris'], $data->toArray());

        $data = $data->when->contains('missing')->concat(['adam']);

        $this->assertSame(['michael', 'tom', 'chris'], $data->toArray());

        $data = $data->unless->contains('missing')->concat(['adam']);

        $this->assertSame(['michael', 'tom', 'chris', 'adam'], $data->toArray());

        $data = $data->unless->contains('adam')->concat(['bogdan']);

        $this->assertSame(['michael', 'tom', 'chris', 'adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnless($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unless(false, function ($data) {
            return $data->concat(['caleb']);
        });

        $this->assertSame(['michael', 'tom', 'caleb'], $data->toArray());

        $data = new $collection(['michael', 'tom']);

        $data = $data->unless(true, function ($data) {
            return $data->concat(['caleb']);
        });

        $this->assertSame(['michael', 'tom'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnlessDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unless(true, function ($data) {
            return $data->concat(['caleb']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'taylor'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnlessEmpty($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unlessEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['michael', 'tom', 'adam'], $data->toArray());

        $data = new $collection;

        $data = $data->unlessEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame([], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnlessEmptyDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unlessEmpty(function ($data) {
            return $data->concat(['adam']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnlessNotEmpty($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unlessNotEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['michael', 'tom'], $data->toArray());

        $data = new $collection;

        $data = $data->unlessNotEmpty(function ($data) {
            return $data->concat(['adam']);
        });

        $this->assertSame(['adam'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUnlessNotEmptyDefault($collection)
    {
        $data = new $collection(['michael', 'tom']);

        $data = $data->unlessNotEmpty(function ($data) {
            return $data->concat(['adam']);
        }, function ($data) {
            return $data->concat(['taylor']);
        });

        $this->assertSame(['michael', 'tom', 'taylor'], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testHasReturnsValidResults($collection)
    {
        $data = new $collection(['foo' => 'one', 'bar' => 'two', 1 => 'three']);
        $this->assertTrue($data->has('foo'));
        $this->assertTrue($data->has('foo', 'bar', 1));
        $this->assertFalse($data->has('foo', 'bar', 1, 'baz'));
        $this->assertFalse($data->has('baz'));
    }

    public function testPutAddsItemToCollection()
    {
        $data = new Collection;
        $this->assertSame([], $data->toArray());
        $data->put('foo', 1);
        $this->assertSame(['foo' => 1], $data->toArray());
        $data->put('bar', ['nested' => 'two']);
        $this->assertSame(['foo' => 1, 'bar' => ['nested' => 'two']], $data->toArray());
        $data->put('foo', 3);
        $this->assertSame(['foo' => 3, 'bar' => ['nested' => 'two']], $data->toArray());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testItThrowsExceptionWhenTryingToAccessNoProxyProperty($collection)
    {
        $data = new $collection;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Property [foo] does not exist on this collection instance.');
        $data->foo;
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGetWithNullReturnsNull($collection)
    {
        $data = new $collection([1, 2, 3]);
        $this->assertNull($data->get(null));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGetWithDefaultValue($collection)
    {
        $data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
        $this->assertEquals('34', $data->get('age', 34));
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testGetWithCallbackAsDefaultValue($collection)
    {
        $data = new $collection(['name' => 'taylor', 'framework' => 'laravel']);
        $result = $data->get('email', function () {
            return 'taylor@example.com';
        });
        $this->assertEquals('taylor@example.com', $result);
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNull($collection)
    {
        $data = new $collection([
            ['name' => 'Taylor'],
            ['name' => null],
            ['name' => 'Bert'],
            ['name' => false],
            ['name' => ''],
        ]);

        $this->assertSame([
            1 => ['name' => null],
        ], $data->whereNull('name')->all());

        $this->assertSame([], $data->whereNull()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNullWithoutKey($collection)
    {
        $collection = new $collection([1, null, 3, 'null', false, true]);
        $this->assertSame([
            1 => null,
        ], $collection->whereNull()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNotNull($collection)
    {
        $data = new $collection($originalData = [
            ['name' => 'Taylor'],
            ['name' => null],
            ['name' => 'Bert'],
            ['name' => false],
            ['name' => ''],
        ]);

        $this->assertSame([
            0 => ['name' => 'Taylor'],
            2 => ['name' => 'Bert'],
            3 => ['name' => false],
            4 => ['name' => ''],
        ], $data->whereNotNull('name')->all());

        $this->assertSame($originalData, $data->whereNotNull()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testWhereNotNullWithoutKey($collection)
    {
        $data = new $collection([1, null, 3, 'null', false, true]);

        $this->assertSame([
            0 => 1,
            2 => 3,
            3 => 'null',
            4 => false,
            5 => true,
        ], $data->whereNotNull()->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testCollect($collection)
    {
        $data = $collection::make([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ])->collect();

        $this->assertInstanceOf(Collection::class, $data);

        $this->assertSame([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ], $data->all());
    }

    /**
     * @dataProvider collectionClassProvider
     */
    public function testUndot($collection)
    {
        $data = $collection::make([
            'name' => 'Taylor',
            'meta.foo' => 'bar',
            'meta.baz' => 'boom',
            'meta.bam.boom' => 'bip',
        ])->undot();
        $this->assertSame([
            'name' => 'Taylor',
            'meta' => [
                'foo' => 'bar',
                'baz' => 'boom',
                'bam' => [
                    'boom' => 'bip',
                ],
            ],
        ], $data->all());

        $data = $collection::make([
            'foo.0' => 'bar',
            'foo.1' => 'baz',
            'foo.baz' => 'boom',
        ])->undot();
        $this->assertSame([
            'foo' => [
                'bar',
                'baz',
                'baz' => 'boom',
            ],
        ], $data->all());
    }

    /**
     * Provides each collection class, respectively.
     *
     * @return array
     */
    public static function collectionClassProvider()
    {
        return [
            [Collection::class],
            [LazyCollection::class],
        ];
    }
}

class TestSupportCollectionHigherOrderItem
{
    public $name;

    public function __construct($name = 'taylor')
    {
        $this->name = $name;
    }

    public function uppercase()
    {
        return $this->name = strtoupper($this->name);
    }

    public function is($name)
    {
        return $this->name === $name;
    }
}

class TestAccessorEloquentTestStub
{
    protected $attributes = [];

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
    }

    public function __get($attribute)
    {
        $accessor = 'get'.lcfirst($attribute).'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }

        return $this->$attribute;
    }

    public function __isset($attribute)
    {
        $accessor = 'get'.lcfirst($attribute).'Attribute';

        if (method_exists($this, $accessor)) {
            return ! is_null($this->$accessor());
        }

        return isset($this->$attribute);
    }

    public function getSomeAttribute()
    {
        return $this->attributes['some'];
    }
}

class TestArrayAccessImplementation implements ArrayAccess
{
    private $arr;

    public function __construct($arr)
    {
        $this->arr = $arr;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->arr[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->arr[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->arr[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->arr[$offset]);
    }
}

class TestArrayableObject implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}

class TestJsonableObject implements Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }
}

class TestJsonSerializeObject implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}

class TestJsonSerializeToStringObject implements JsonSerializable
{
    public function jsonSerialize(): string
    {
        return 'foobar';
    }
}

class TestJsonSerializeWithScalarValueObject implements JsonSerializable
{
    public function jsonSerialize(): string
    {
        return 'foo';
    }
}

class TestTraversableAndJsonSerializableObject implements IteratorAggregate, JsonSerializable
{
    public $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function jsonSerialize(): array
    {
        return json_decode(json_encode($this->items), true);
    }
}

class TestCollectionMapIntoObject
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class TestCollectionSubclass extends Collection
{
    //
}
