<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;

class DatabaseEloquentCollectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAddingItemsToCollection()
	{
		$c = new Collection(array('foo'));
		$c->add('bar')->add('baz');
		$this->assertEquals(array('foo', 'bar', 'baz'), $c->all());
	}


	public function testContainsIndicatesIfKeyedModelInArray()
	{
		$mockModel = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection(array($mockModel));
		$mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$c->add($mockModel2);

		$this->assertTrue($c->contains(1));
		$this->assertTrue($c->contains(2));
		$this->assertFalse($c->contains(3));
	}


	public function testFindMethodFindsModelById()
	{
		$mockModel = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection(array($mockModel));

		$this->assertTrue($mockModel === $c->find(1));
		$this->assertTrue('taylor' === $c->find(2, 'taylor'));
	}


	public function testLoadMethodEagerLoadsGivenRelationships()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', array('first'), array(array('foo')));
		$mockItem = m::mock('StdClass');
		$c->expects($this->once())->method('first')->will($this->returnValue($mockItem));
		$mockItem->shouldReceive('newQuery')->once()->andReturn($mockItem);
		$mockItem->shouldReceive('with')->with(array('bar', 'baz'))->andReturn($mockItem);
		$mockItem->shouldReceive('eagerLoadRelations')->once()->with(array('foo'))->andReturn(array('results'));
		$c->load('bar', 'baz');

		$this->assertEquals(array('results'), $c->all());
	}

}