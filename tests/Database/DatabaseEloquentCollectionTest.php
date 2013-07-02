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


	public function testCollectionDictionaryReturnsModelKeys()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection(array($one, $two, $three));

		$this->assertEquals(array(1,2,3), $c->modelKeys());
	}


	public function testLists()
	{
		$data = new Collection(array((object) array('name' => 'taylor', 'email' => 'foo'), (object) array('name' => 'dayle', 'email' => 'bar')));
		$this->assertEquals(array('taylor' => 'foo', 'dayle' => 'bar'), $data->lists('email', 'name'));
		$this->assertEquals(array('foo', 'bar'), $data->lists('email'));
	}

}