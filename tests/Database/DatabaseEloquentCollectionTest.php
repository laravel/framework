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
		$c = new Collection(['foo']);
		$c->add('bar')->add('baz');
		$this->assertEquals(['foo', 'bar', 'baz'], $c->all());
	}


	public function testGettingMaxItemsFromCollection()
	{
		$c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
		$this->assertEquals(20, $c->max('foo'));
	}


	public function testGettingMinItemsFromCollection()
	{
		$c = new Collection([(object) ['foo' => 10], (object) ['foo' => 20]]);
		$this->assertEquals(10, $c->min('foo'));
	}


	public function testContainsIndicatesIfModelInArray()
	{
		$mockModel = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$mockModel3 = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel3->shouldReceive('getKey')->andReturn(3);
		$c = new Collection([$mockModel, $mockModel2]);

		$this->assertTrue($c->contains($mockModel));
		$this->assertTrue($c->contains($mockModel2));
		$this->assertFalse($c->contains($mockModel3));
	}


	public function testContainsIndicatesIfKeyedModelInArray()
	{
		$mockModel = m::mock('Illuminate\Database\Eloquent\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection([$mockModel]);
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
		$c = new Collection([$mockModel]);

		$this->assertSame($mockModel, $c->find(1));
		$this->assertSame('taylor', $c->find(2, 'taylor'));
	}


	public function testLoadMethodEagerLoadsGivenRelationships()
	{
		$c = $this->getMock('Illuminate\Database\Eloquent\Collection', ['first'], [['foo']]);
		$mockItem = m::mock('StdClass');
		$c->expects($this->once())->method('first')->will($this->returnValue($mockItem));
		$mockItem->shouldReceive('newQuery')->once()->andReturn($mockItem);
		$mockItem->shouldReceive('with')->with(['bar', 'baz'])->andReturn($mockItem);
		$mockItem->shouldReceive('eagerLoadRelations')->once()->with(['foo'])->andReturn(['results']);
		$c->load('bar', 'baz');

		$this->assertEquals(['results'], $c->all());
	}


	public function testCollectionDictionaryReturnsModelKeys()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection([$one, $two, $three]);

		$this->assertEquals([1,2,3], $c->modelKeys());
	}


	public function testCollectionMergesWithGivenCollection()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection([$one, $two]);
		$c2 = new Collection([$two, $three]);

		$this->assertEquals(new Collection([$one, $two, $three]), $c1->merge($c2));
	}


	public function testCollectionDiffsWithGivenCollection()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection([$one, $two]);
		$c2 = new Collection([$two, $three]);

		$this->assertEquals(new Collection([$one]), $c1->diff($c2));
	}


	public function testCollectionIntersectsWithGivenCollection()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection([$one, $two]);
		$c2 = new Collection([$two, $three]);

		$this->assertEquals(new Collection([$two]), $c1->intersect($c2));
	}


	public function testCollectionReturnsUniqueItems()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$c = new Collection([$one, $two, $two]);

		$this->assertEquals(new Collection([$one, $two]), $c->unique());
	}


	public function testLists()
	{
		$data = new Collection([(object) ['name' => 'taylor', 'email' => 'foo'], (object) ['name' => 'dayle', 'email' => 'bar']]);
		$this->assertEquals(['taylor' => 'foo', 'dayle' => 'bar'], $data->lists('email', 'name'));
		$this->assertEquals(['foo', 'bar'], $data->lists('email'));
	}


	public function testOnlyReturnsCollectionWithGivenModelKeys()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection([$one, $two, $three]);

		$this->assertEquals(new Collection([$one]), $c->only(1));
		$this->assertEquals(new Collection([$two, $three]), $c->only([2, 3]));
	}


	public function testExceptReturnsCollectionWithoutGivenModelKeys()
	{
		$one = m::mock('Illuminate\Database\Eloquent\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Illuminate\Database\Eloquent\Model');
		$two->shouldReceive('getKey')->andReturn('2');

		$three = m::mock('Illuminate\Database\Eloquent\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection([$one, $two, $three]);

		$this->assertEquals(new Collection([$one, $three]), $c->except(2));
		$this->assertEquals(new Collection([$one]), $c->except([2, 3]));
	}

}
