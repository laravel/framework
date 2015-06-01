<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Model;
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


	public function testGettingMaxItemsFromCollection()
	{
		$c = new Collection(array((object) array('foo' => 10), (object) array('foo' => 20)));
		$this->assertEquals(20, $c->max('foo'));
	}


	public function testGettingMinItemsFromCollection()
	{
		$c = new Collection(array((object) array('foo' => 10), (object) array('foo' => 20)));
		$this->assertEquals(10, $c->min('foo'));
	}


	public function testContainsIndicatesIfModelInArray()
	{
		$mockModel = m::mock(Model::class);
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$mockModel2 = m::mock(Model::class);
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$mockModel3 = m::mock(Model::class);
		$mockModel3->shouldReceive('getKey')->andReturn(3);
		$c = new Collection(array($mockModel, $mockModel2));

		$this->assertTrue($c->contains($mockModel));
		$this->assertTrue($c->contains($mockModel2));
		$this->assertFalse($c->contains($mockModel3));
	}


	public function testContainsIndicatesIfKeyedModelInArray()
	{
		$mockModel = m::mock(Model::class);
		$mockModel->shouldReceive('getKey')->andReturn('1');
		$c = new Collection(array($mockModel));
		$mockModel2 = m::mock(Model::class);
		$mockModel2->shouldReceive('getKey')->andReturn('2');
		$c->add($mockModel2);

		$this->assertTrue($c->contains(1));
		$this->assertTrue($c->contains(2));
		$this->assertFalse($c->contains(3));
	}

	public function testContainsKeyAndValueIndicatesIfModelInArray()
	{
		$mockModel1 = m::mock(Model::class);
		$mockModel1->shouldReceive('offsetExists')->with('name')->andReturn(true);
		$mockModel1->shouldReceive('offsetGet')->with('name')->andReturn('Taylor');
		$mockModel2 = m::mock(Model::class);
		$mockModel2->shouldReceive('offsetExists')->andReturn(true);
		$mockModel2->shouldReceive('offsetGet')->with('name')->andReturn('Abigail');
		$c = new Collection([$mockModel1, $mockModel2]);

		$this->assertTrue($c->contains('name', 'Taylor'));
		$this->assertTrue($c->contains('name', 'Abigail'));
		$this->assertFalse($c->contains('name', 'Dayle'));
	}


	public function testContainsClosureIndicatesIfModelInArray()
	{
		$mockModel1 = m::mock(Model::class);
		$mockModel1->shouldReceive('getKey')->andReturn(1);
		$mockModel2 = m::mock(Model::class);
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$c = new Collection([$mockModel1, $mockModel2]);

		$this->assertTrue($c->contains(function($k, $m){ return $m->getKey() < 2; }));
		$this->assertFalse($c->contains(function($k, $m){ return $m->getKey() > 2; }));
	}


	public function testFindMethodFindsModelById()
	{
		$mockModel = m::mock(Model::class);
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection(array($mockModel));

		$this->assertSame($mockModel, $c->find(1));
		$this->assertSame('taylor', $c->find(2, 'taylor'));
	}


	public function testLoadMethodEagerLoadsGivenRelationships()
	{
		$c = $this->getMock(Collection::class, array('first'), array(array('foo')));
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
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection(array($one, $two, $three));

		$this->assertEquals(array(1,2,3), $c->modelKeys());
	}


	public function testCollectionMergesWithGivenCollection()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($one, $two, $three)), $c1->merge($c2));
	}


	public function testCollectionDiffsWithGivenCollection()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($one)), $c1->diff($c2));
	}


	public function testCollectionIntersectsWithGivenCollection()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($two)), $c1->intersect($c2));
	}


	public function testCollectionReturnsUniqueItems()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$c = new Collection(array($one, $two, $two));

		$this->assertEquals(new Collection(array($one, $two)), $c->unique());
	}


	public function testOnlyReturnsCollectionWithGivenModelKeys()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection(array($one, $two, $three));

		$this->assertEquals(new Collection(array($one)), $c->only(1));
		$this->assertEquals(new Collection(array($two, $three)), $c->only(array(2, 3)));
	}


	public function testExceptReturnsCollectionWithoutGivenModelKeys()
	{
		$one = m::mock(Model::class);
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock(Model::class);
		$two->shouldReceive('getKey')->andReturn('2');

		$three = m::mock(Model::class);
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection(array($one, $two, $three));

		$this->assertEquals(new Collection(array($one, $three)), $c->except(2));
		$this->assertEquals(new Collection(array($one)), $c->except(array(2, 3)));
	}

}
