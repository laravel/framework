<?php

use Mockery as m;
use Illuminate\Support\Collection;

class SupportCollectionTest extends PHPUnit_Framework_TestCase {

	public function testFirstReturnsFirstItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertEquals('foo', $c->first());
	}


	public function testLastReturnsLastItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));

		$this->assertEquals('bar', $c->last());
	}


	public function testPopReturnsAndRemovesLastItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));

		$this->assertEquals('bar', $c->pop());
		$this->assertEquals('foo', $c->first());
	}


	public function testShiftReturnsAndRemovesFirstItemInCollection()
	{
		$c = new Collection(array('foo', 'bar'));

		$this->assertEquals('foo', $c->shift());
		$this->assertEquals('bar', $c->first());
	}


	public function testEmptyCollectionIsEmpty()
	{
		$c = new Collection();

		$this->assertTrue($c->isEmpty());
	}


	public function testToArrayCallsToArrayOnEachItemInCollection()
	{
		$item1 = m::mock('Illuminate\Contracts\Support\Arrayable');
		$item1->shouldReceive('toArray')->once()->andReturn('foo.array');
		$item2 = m::mock('Illuminate\Contracts\Support\Arrayable');
		$item2->shouldReceive('toArray')->once()->andReturn('bar.array');
		$c = new Collection(array($item1, $item2));
		$results = $c->toArray();

		$this->assertEquals(array('foo.array', 'bar.array'), $results);
	}


	public function testToJsonEncodesTheToArrayResult()
	{
		$c = $this->getMock('Illuminate\Support\Collection', array('toArray'));
		$c->expects($this->once())->method('toArray')->will($this->returnValue('foo'));
		$results = $c->toJson();

		$this->assertEquals(json_encode('foo'), $results);
	}


	public function testCastingToStringJsonEncodesTheToArrayResult()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', array('toArray'));
		$c->expects($this->once())->method('toArray')->will($this->returnValue('foo'));

		$this->assertEquals(json_encode('foo'), (string) $c);
	}


	public function testOffsetAccess()
	{
		$c = new Collection(array('name' => 'taylor'));
		$this->assertEquals('taylor', $c['name']);
		$c['name'] = 'dayle';
		$this->assertEquals('dayle', $c['name']);
		$this->assertTrue(isset($c['name']));
		unset($c['name']);
		$this->assertFalse(isset($c['name']));
		$c[] = 'jason';
		$this->assertEquals('jason', $c[0]);
	}


	public function testCountable()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertCount(2, $c);
	}


	public function testIterable()
	{
		$c = new Collection(array('foo'));
		$this->assertInstanceOf('ArrayIterator', $c->getIterator());
		$this->assertEquals(array('foo'), $c->getIterator()->getArrayCopy());
	}


	public function testCachingIterator()
	{
		$c = new Collection(array('foo'));
		$this->assertInstanceOf('CachingIterator', $c->getCachingIterator());
	}


	public function testFilter()
	{
		$c = new Collection(array(array('id' => 1, 'name' => 'Hello'), array('id' => 2, 'name' => 'World')));
		$this->assertEquals(array(1 => array('id' => 2, 'name' => 'World')), $c->filter(function($item)
		{
			return $item['id'] == 2;
		})->all());
	}


	public function testWhere()
	{
		$c = new Collection([['v' => 1], ['v' => 2], ['v' => 3], ['v' => '3'], ['v' => 4]]);

		$this->assertEquals([['v' => 3]], $c->where('v', 3)->values()->all());
		$this->assertEquals([['v' => 3], ['v' => '3']], $c->whereLoose('v', 3)->values()->all());
	}


	public function testValues()
	{
		$c = new Collection(array(array('id' => 1, 'name' => 'Hello'), array('id' => 2, 'name' => 'World')));
		$this->assertEquals(array(array('id' => 2, 'name' => 'World')), $c->filter(function($item)
		{
			return $item['id'] == 2;
		})->values()->all());
	}


	public function testFlatten()
	{
		$c = new Collection(array(array('#foo', '#bar'), array('#baz')));
		$this->assertEquals(array('#foo', '#bar', '#baz'), $c->flatten()->all());
	}


	public function testMergeArray()
	{
		$c = new Collection(array('name' => 'Hello'));
		$this->assertEquals(array('name' => 'Hello', 'id' => 1), $c->merge(array('id' => 1))->all());
	}


	public function testMergeCollection()
	{
		$c = new Collection(array('name' => 'Hello'));
		$this->assertEquals(array('name' => 'World', 'id' => 1), $c->merge(new Collection(array('name' => 'World', 'id' => 1)))->all());
	}


	public function testDiffCollection()
	{
		$c = new Collection(array('id' => 1, 'first_word' => 'Hello'));
		$this->assertEquals(array('id' => 1), $c->diff(new Collection(array('first_word' => 'Hello', 'last_word' => 'World')))->all());
	}


	public function testIntersectCollection()
	{
		$c = new Collection(array('id' => 1, 'first_word' => 'Hello'));
		$this->assertEquals(array('first_word' => 'Hello'), $c->intersect(new Collection(array('first_world' => 'Hello', 'last_word' => 'World')))->all());
	}


	public function testUnique()
	{
		$c = new Collection(array('Hello', 'World', 'World'));
		$this->assertEquals(array('Hello', 'World'), $c->unique()->all());
	}


	public function testCollapse()
	{
		$data = new Collection(array(array($object1 = new StdClass), array($object2 = new StdClass)));
		$this->assertEquals(array($object1, $object2), $data->collapse()->all());
	}


	public function testCollapseWithNestedCollactions()
	{
		$data = new Collection([new Collection([1, 2, 3]), new Collection([4, 5, 6])]);
		$this->assertEquals([1, 2, 3, 4, 5, 6], $data->collapse()->all());
	}


	public function testSort()
	{
		$data = new Collection(array(5, 3, 1, 2, 4));
		$data->sort(function($a, $b)
		{
			if ($a === $b)
			{
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		});

		$this->assertEquals(range(1, 5), array_values($data->all()));
	}


	public function testSortBy()
	{
		$data = new Collection(array('taylor', 'dayle'));
		$data = $data->sortBy(function($x) { return $x; });

		$this->assertEquals(array('dayle', 'taylor'), array_values($data->all()));

		$data = new Collection(array('dayle', 'taylor'));
		$data->sortByDesc(function($x) { return $x; });

		$this->assertEquals(array('taylor', 'dayle'), array_values($data->all()));
	}


	public function testSortByString()
	{
		$data = new Collection(array(array('name' => 'taylor'), array('name' => 'dayle')));
		$data = $data->sortBy('name');

		$this->assertEquals(array(array('name' => 'dayle'), array('name' => 'taylor')), array_values($data->all()));
	}


	public function testReverse()
	{
		$data = new Collection(array('zaeed', 'alan'));
		$reversed = $data->reverse();

		$this->assertEquals(array('alan', 'zaeed'), array_values($reversed->all()));
	}


	public function testFlip()
	{
		$data = new Collection(array('name' => 'taylor', 'framework' => 'laravel'));
		$this->assertEquals(array('taylor' => 'name', 'laravel' => 'framework'), $data->flip()->toArray());
	}


	public function testChunk()
	{
		$data = new Collection(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10));
		$data = $data->chunk(3);

		$this->assertInstanceOf('Illuminate\Support\Collection', $data);
		$this->assertInstanceOf('Illuminate\Support\Collection', $data[0]);
		$this->assertEquals(4, $data->count());
		$this->assertEquals(array(1, 2, 3), $data[0]->toArray());
		$this->assertEquals(array(10), $data[3]->toArray());
	}


	public function testListsWithArrayAndObjectValues()
	{
		$data = new Collection(array((object) array('name' => 'taylor', 'email' => 'foo'), array('name' => 'dayle', 'email' => 'bar')));
		$this->assertEquals(array('taylor' => 'foo', 'dayle' => 'bar'), $data->lists('email', 'name'));
		$this->assertEquals(array('foo', 'bar'), $data->lists('email'));
	}


	public function testListsWithArrayAccessValues()
	{
		$data = new Collection(array(
			new TestArrayAccessImplementation(array('name' => 'taylor', 'email' => 'foo')),
			new TestArrayAccessImplementation(array('name' => 'dayle', 'email' => 'bar'))
		));

		$this->assertEquals(array('taylor' => 'foo', 'dayle' => 'bar'), $data->lists('email', 'name'));
		$this->assertEquals(array('foo', 'bar'), $data->lists('email'));
	}


	public function testImplode()
	{
		$data = new Collection([['name' => 'taylor', 'email' => 'foo'], ['name' => 'dayle', 'email' => 'bar']]);
		$this->assertEquals('foobar', $data->implode('email'));
		$this->assertEquals('foo,bar', $data->implode('email', ','));

		$data = new Collection(['taylor', 'dayle']);
		$this->assertEquals('taylordayle', $data->implode(''));
		$this->assertEquals('taylor,dayle', $data->implode(','));
	}


	public function testTake()
	{
		$data = new Collection(array('taylor', 'dayle', 'shawn'));
		$data = $data->take(2);
		$this->assertEquals(array('taylor', 'dayle'), $data->all());
	}


	public function testRandom()
	{
		$data = new Collection(array(1, 2, 3, 4, 5, 6));
		$random = $data->random();
		$this->assertInternalType('integer', $random);
		$this->assertContains($random, $data->all());
		$random = $data->random(3);
		$this->assertCount(3, $random);
	}


	public function testRandomOnEmpty()
	{
		$data = new Collection();
		$random = $data->random();
		$this->assertNull($random);
	}


	public function testTakeLast()
	{
		$data = new Collection(array('taylor', 'dayle', 'shawn'));
		$data = $data->take(-2);
		$this->assertEquals(array('dayle', 'shawn'), $data->all());
	}


	public function testTakeAll()
	{
		$data = new Collection(array('taylor', 'dayle', 'shawn'));
		$data = $data->take();
		$this->assertEquals(array('taylor', 'dayle', 'shawn'), $data->all());
	}


	public function testMakeMethod()
	{
		$collection = Collection::make('foo');
		$this->assertEquals(array('foo'), $collection->all());
	}


	public function testMakeMethodFromNull()
	{
		$collection = Collection::make(null);
		$this->assertEquals([], $collection->all());

		$collection = Collection::make();
		$this->assertEquals([], $collection->all());
	}


	public function testMakeMethodFromCollection()
	{
		$firstCollection = Collection::make(['foo' => 'bar']);
		$secondCollection = Collection::make($firstCollection);
		$this->assertEquals(['foo' => 'bar'], $secondCollection->all());
	}


	public function testMakeMethodFromArray()
	{
		$collection = Collection::make(['foo' => 'bar']);
		$this->assertEquals(['foo' => 'bar'], $collection->all());
	}

	public function testConstructMakeFromObject()
	{
		$object = new stdClass();
		$object->foo = 'bar';
		$collection = Collection::make($object);
		$this->assertEquals(['foo' => 'bar'], $collection->all());
	}


	public function testConstructMethod()
	{
		$collection = new Collection('foo');
		$this->assertEquals(array('foo'), $collection->all());
	}


	public function testConstructMethodFromNull()
	{
		$collection = new Collection(null);
		$this->assertEquals([], $collection->all());

		$collection = new Collection();
		$this->assertEquals([], $collection->all());
	}


	public function testConstructMethodFromCollection()
	{
		$firstCollection = new Collection(['foo' => 'bar']);
		$secondCollection = new Collection($firstCollection);
		$this->assertEquals(['foo' => 'bar'], $secondCollection->all());
	}


	public function testConstructMethodFromArray()
	{
		$collection = new Collection(['foo' => 'bar']);
		$this->assertEquals(['foo' => 'bar'], $collection->all());
	}


	public function testConstructMethodFromObject()
	{
		$object = new stdClass();
		$object->foo = 'bar';
		$collection = new Collection($object);
		$this->assertEquals(['foo' => 'bar'], $collection->all());
	}


	public function testSplice()
	{
		$data = new Collection(array('foo', 'baz'));
		$data->splice(1, 0, 'bar');
		$this->assertEquals(array('foo', 'bar', 'baz'), $data->all());

		$data = new Collection(array('foo', 'baz'));
		$data->splice(1, 1);
		$this->assertEquals(array('foo'), $data->all());

		$data = new Collection(array('foo', 'baz'));
		$cut = $data->splice(1, 1, 'bar');
		$this->assertEquals(array('foo', 'bar'), $data->all());
		$this->assertEquals(array('baz'), $cut->all());
	}


	public function testGetListValueWithAccessors()
	{
		$model    = new TestAccessorEloquentTestStub(array('some' => 'foo'));
		$modelTwo = new TestAccessorEloquentTestStub(array('some' => 'bar'));
		$data     = new Collection(array($model, $modelTwo));

		$this->assertEquals(array('foo', 'bar'), $data->lists('some'));
	}


	public function testTransform()
	{
		$data = new Collection(array('taylor', 'colin', 'shawn'));
		$data->transform(function($item) { return strrev($item); });
		$this->assertEquals(array('rolyat', 'niloc', 'nwahs'), array_values($data->all()));
	}


	public function testFirstWithCallback()
	{
		$data = new Collection(array('foo', 'bar', 'baz'));
		$result = $data->first(function($key, $value) { return $value === 'bar'; });
		$this->assertEquals('bar', $result);
	}


	public function testFirstWithCallbackAndDefault()
	{
		$data = new Collection(array('foo', 'bar'));
		$result = $data->first(function($key, $value) { return $value === 'baz'; }, 'default');
		$this->assertEquals('default', $result);
	}


	public function testGroupByAttribute()
	{
		$data = new Collection(array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1'), array('rating' => 2, 'url' => '2')));

		$result = $data->groupBy('rating');
		$this->assertEquals(array(1 => array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1')), 2 => array(array('rating' => 2, 'url' => '2'))), $result->toArray());

		$result = $data->groupBy('url');
		$this->assertEquals(array(1 => array(array('rating' => 1, 'url' => '1'), array('rating' => 1, 'url' => '1')), 2 => array(array('rating' => 2, 'url' => '2'))), $result->toArray());
	}


	public function testKeyByAttribute()
	{
		$data = new Collection([['rating' => 1, 'name' => '1'], ['rating' => 2, 'name' => '2'], ['rating' => 3, 'name' => '3']]);

		$result = $data->keyBy('rating');
		$this->assertEquals([1 => ['rating' => 1, 'name' => '1'], 2 => ['rating' => 2, 'name' => '2'], 3 => ['rating' => 3, 'name' => '3']], $result->all());

		$result = $data->keyBy(function($item){ return $item['rating'] * 2; });
		$this->assertEquals([2 => ['rating' => 1, 'name' => '1'], 4 => ['rating' => 2, 'name' => '2'], 6 => ['rating' => 3, 'name' => '3']], $result->all());
	}


	public function testContains()
	{
		$c = new Collection([1, 3, 5]);

		$this->assertTrue($c->contains(1));
		$this->assertFalse($c->contains(2));
		$this->assertTrue($c->contains(function($value) { return $value < 5; }));
		$this->assertFalse($c->contains(function($value) { return $value > 5; }));

		$c = new Collection([['v' => 1], ['v' => 3], ['v' => 5]]);

		$this->assertTrue($c->contains('v', 1));
		$this->assertFalse($c->contains('v', 2));

		$c = new Collection(['date', 'class', (object) ['foo' => 50]]);

		$this->assertTrue($c->contains('date'));
		$this->assertTrue($c->contains('class'));
		$this->assertFalse($c->contains('foo'));
	}


	public function testGettingSumFromCollection()
	{
		$c = new Collection(array((object) array('foo' => 50), (object) array('foo' => 50)));
		$this->assertEquals(100, $c->sum('foo'));

		$c = new Collection(array((object) array('foo' => 50), (object) array('foo' => 50)));
		$this->assertEquals(100, $c->sum(function($i) { return $i->foo; }));
	}


	public function testCanSumValuesWithoutACallback()
	{
		$c = new Collection([1, 2, 3, 4, 5]);
		$this->assertEquals(15, $c->sum());
	}


	public function testGettingSumFromEmptyCollection()
	{
		$c = new Collection();
		$this->assertEquals(0, $c->sum('foo'));
	}


	public function testValueRetrieverAcceptsDotNotation()
	{
		$c = new Collection(array(
			(object) array('id' => 1, 'foo' => array('bar' => 'B')), (object) array('id' => 2, 'foo' => array('bar' => 'A'))
		));

		$c = $c->sortBy('foo.bar');
		$this->assertEquals(array(2, 1), $c->lists('id'));
	}


	public function testPullRetrievesItemFromCollection()
	{
		$c = new Collection(array('foo', 'bar'));

		$this->assertEquals('foo', $c->pull(0));
	}


	public function testPullRemovesItemFromCollection()
	{
		$c = new Collection(array('foo', 'bar'));
		$c->pull(0);
		$this->assertEquals(array(1 => 'bar'), $c->all());
	}


	public function testPullReturnsDefault()
	{
		$c = new Collection(array());
		$value = $c->pull(0, 'foo');
		$this->assertEquals('foo', $value);
	}


	public function testRejectRemovesElementsPassingTruthTest()
	{
		$c = new Collection(['foo', 'bar']);
		$this->assertEquals(['foo'], $c->reject('bar')->values()->all());

		$c = new Collection(['foo', 'bar']);
		$this->assertEquals(['foo'], $c->reject(function($v) { return $v == 'bar'; })->values()->all());

		$c = new Collection(['foo', null]);
		$this->assertEquals(['foo'], $c->reject(null)->values()->all());

		$c = new Collection(['foo', 'bar']);
		$this->assertEquals(['foo', 'bar'], $c->reject('baz')->values()->all());

		$c = new Collection(['foo', 'bar']);
		$this->assertEquals(['foo', 'bar'], $c->reject(function($v) { return $v == 'baz'; })->values()->all());
	}


	public function testSearchReturnsIndexOfFirstFoundItem()
	{
		$c = new Collection([1, 2, 3, 4, 5, 2, 5, 'foo' => 'bar']);

		$this->assertEquals(1, $c->search(2));
		$this->assertEquals('foo', $c->search('bar'));
		$this->assertEquals(4, $c->search(function($value){ return $value > 4; }));
		$this->assertEquals('foo', $c->search(function($value){ return ! is_numeric($value); }));
	}


	public function testSearchReturnsFalseWhenItemIsNotFound()
	{
		$c = new Collection([1, 2, 3, 4, 5, 'foo' => 'bar']);

		$this->assertFalse($c->search(6));
		$this->assertFalse($c->search('foo'));
		$this->assertFalse($c->search(function($value){ return $value < 1 && is_numeric($value); }));
		$this->assertFalse($c->search(function($value){ return $value == 'nope'; }));
	}


	public function testKeys()
	{
		$c = new Collection(array('name' => 'taylor', 'framework' => 'laravel'));
		$this->assertEquals(array('name', 'framework'), $c->keys()->all());
	}


	public function testPaginate()
	{
		$c = new Collection(['one', 'two', 'three', 'four']);
		$this->assertEquals(['one', 'two'], $c->forPage(1, 2)->all());
		$this->assertEquals(['three', 'four'], $c->forPage(2, 2)->all());
		$this->assertEquals([], $c->forPage(3, 2)->all());
	}

}

class TestAccessorEloquentTestStub
{
	protected $attributes = array();

	public function __construct($attributes)
	{
		$this->attributes = $attributes;
	}


	public function __get($attribute)
	{
		$accessor = 'get' .lcfirst($attribute). 'Attribute';
		if (method_exists($this, $accessor)) {
			return $this->$accessor();
		}

		return $this->$attribute;
	}

	public function __isset($attribute)
	{
		$accessor = 'get' .lcfirst($attribute). 'Attribute';

		if (method_exists($this, $accessor)) {
			return !is_null($this->$accessor());
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

	public function offsetExists($offset)
	{
		return isset($this->arr[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->arr[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->arr[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->arr[$offset]);
	}
}
