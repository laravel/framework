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
		$item1 = m::mock('stdClass');
		$item1->shouldReceive('toArray')->once()->andReturn('foo.array');
		$item2 = m::mock('stdClass');
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
	}


	public function testCountable()
	{
		$c = new Collection(array('foo', 'bar'));
		$this->assertEquals(2, count($c));
	}


	public function testIterable()
	{
		$c = new Collection(array('foo'));
		$this->assertInstanceOf('ArrayIterator', $c->getIterator());
		$this->assertEquals(array('foo'), $c->getIterator()->getArrayCopy());
	}


	public function testFilter()
	{
		$c = new Collection(array(array('id' => 1, 'name' => 'Hello'), array('id' => 2, 'name' => 'World')));
		$this->assertEquals(array(1 => array('id' => 2, 'name' => 'World')), $c->filter(function($item)
		{
			return $item['id'] == 2;
		})->all());
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
		$this->assertEquals(array('#foo', '#bar', '#baz'), $c->flatten());
	}


	public function testMerge()
	{
		$data = new Collection(array(array($object1 = new StdClass), array($object2 = new StdClass)));
		$this->assertEquals(array($object1, $object2), $data->merge()->all());
	}

}
