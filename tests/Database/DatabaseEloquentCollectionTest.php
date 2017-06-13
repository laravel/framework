<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class DatabaseEloquentCollectionTest extends TestCase
{
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

    public function testContainsWithMultipleArguments()
    {
        $c = new Collection([['id' => 1], ['id' => 2]]);

        $this->assertTrue($c->contains('id', 1));
        $this->assertTrue($c->contains('id', '>=', 2));
        $this->assertFalse($c->contains('id', '>', 2));
    }

    public function testContainsIndicatesIfModelInArray()
    {
        $mockModel = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel->shouldReceive('is')->with($mockModel)->andReturn(true);
        $mockModel->shouldReceive('is')->andReturn(false);
        $mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel2->shouldReceive('is')->with($mockModel2)->andReturn(true);
        $mockModel2->shouldReceive('is')->andReturn(false);
        $mockModel3 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel3->shouldReceive('is')->with($mockModel3)->andReturn(true);
        $mockModel3->shouldReceive('is')->andReturn(false);
        $c = new Collection([$mockModel, $mockModel2]);

        $this->assertTrue($c->contains($mockModel));
        $this->assertTrue($c->contains($mockModel2));
        $this->assertFalse($c->contains($mockModel3));
    }

    public function testContainsIndicatesIfDiffentModelInArray()
    {
        $mockModelFoo = m::namedMock('Foo', 'Illuminate\Database\Eloquent\Model');
        $mockModelFoo->shouldReceive('is')->with($mockModelFoo)->andReturn(true);
        $mockModelFoo->shouldReceive('is')->andReturn(false);
        $mockModelBar = m::namedMock('Bar', 'Illuminate\Database\Eloquent\Model');
        $mockModelBar->shouldReceive('is')->with($mockModelBar)->andReturn(true);
        $mockModelBar->shouldReceive('is')->andReturn(false);
        $c = new Collection([$mockModelFoo]);

        $this->assertTrue($c->contains($mockModelFoo));
        $this->assertFalse($c->contains($mockModelBar));
    }

    public function testContainsIndicatesIfKeyedModelInArray()
    {
        $mockModel = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel->shouldReceive('getKey')->andReturn('1');
        $c = new Collection([$mockModel]);
        $mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel2->shouldReceive('getKey')->andReturn('2');
        $c->add($mockModel2);

        $this->assertTrue($c->contains(1));
        $this->assertTrue($c->contains(2));
        $this->assertFalse($c->contains(3));
    }

    public function testContainsKeyAndValueIndicatesIfModelInArray()
    {
        $mockModel1 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel1->shouldReceive('offsetExists')->with('name')->andReturn(true);
        $mockModel1->shouldReceive('offsetGet')->with('name')->andReturn('Taylor');
        $mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel2->shouldReceive('offsetExists')->andReturn(true);
        $mockModel2->shouldReceive('offsetGet')->with('name')->andReturn('Abigail');
        $c = new Collection([$mockModel1, $mockModel2]);

        $this->assertTrue($c->contains('name', 'Taylor'));
        $this->assertTrue($c->contains('name', 'Abigail'));
        $this->assertFalse($c->contains('name', 'Dayle'));
    }

    public function testContainsClosureIndicatesIfModelInArray()
    {
        $mockModel1 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel1->shouldReceive('getKey')->andReturn(1);
        $mockModel2 = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel2->shouldReceive('getKey')->andReturn(2);
        $c = new Collection([$mockModel1, $mockModel2]);

        $this->assertTrue($c->contains(function ($model) {
            return $model->getKey() < 2;
        }));
        $this->assertFalse($c->contains(function ($model) {
            return $model->getKey() > 2;
        }));
    }

    public function testFindMethodFindsModelById()
    {
        $mockModel = m::mock('Illuminate\Database\Eloquent\Model');
        $mockModel->shouldReceive('getKey')->andReturn(1);
        $c = new Collection([$mockModel]);

        $this->assertSame($mockModel, $c->find(1));
        $this->assertSame('taylor', $c->find(2, 'taylor'));
    }

    public function testFindMethodFindsManyModelsById()
    {
        $model1 = (new TestEloquentCollectionModel)->forceFill(['id' => 1]);
        $model2 = (new TestEloquentCollectionModel)->forceFill(['id' => 2]);
        $model3 = (new TestEloquentCollectionModel)->forceFill(['id' => 3]);

        $c = new Collection;
        $this->assertInstanceOf(Collection::class, $c->find([]));
        $this->assertCount(0, $c->find([1]));

        $c->push($model1);
        $this->assertCount(1, $c->find([1]));
        $this->assertEquals(1, $c->find([1])->first()->id);
        $this->assertCount(0, $c->find([2]));

        $c->push($model2)->push($model3);
        $this->assertCount(1, $c->find([2]));
        $this->assertEquals(2, $c->find([2])->first()->id);
        $this->assertCount(2, $c->find([2, 3, 4]));
        $this->assertEquals([2, 3], $c->find([2, 3, 4])->pluck('id')->all());
    }

    public function testLoadMethodEagerLoadsGivenRelationships()
    {
        $c = $this->getMockBuilder('Illuminate\Database\Eloquent\Collection')->setMethods(['first'])->setConstructorArgs([['foo']])->getMock();
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

        $this->assertEquals([1, 2, 3], $c->modelKeys());
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

    public function testMap()
    {
        $one = m::mock('Illuminate\Database\Eloquent\Model');
        $two = m::mock('Illuminate\Database\Eloquent\Model');

        $c = new Collection([$one, $two]);

        $cAfterMap = $c->map(function ($item) {
            return $item;
        });

        $this->assertEquals($c->all(), $cAfterMap->all());
        $this->assertInstanceOf(Collection::class, $cAfterMap);
    }

    public function testMappingToNonModelsReturnsABaseCollection()
    {
        $one = m::mock('Illuminate\Database\Eloquent\Model');
        $two = m::mock('Illuminate\Database\Eloquent\Model');

        $c = (new Collection([$one, $two]))->map(function ($item) {
            return 'not-a-model';
        });

        $this->assertEquals(BaseCollection::class, get_class($c));
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

    public function testOnlyReturnsCollectionWithGivenModelKeys()
    {
        $one = m::mock('Illuminate\Database\Eloquent\Model');
        $one->shouldReceive('getKey')->andReturn(1);

        $two = m::mock('Illuminate\Database\Eloquent\Model');
        $two->shouldReceive('getKey')->andReturn(2);

        $three = m::mock('Illuminate\Database\Eloquent\Model');
        $three->shouldReceive('getKey')->andReturn(3);

        $c = new Collection([$one, $two, $three]);

        $this->assertEquals($c, $c->only(null));
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

    public function testMakeHiddenAddsHiddenOnEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeHidden(['visible']);

        $this->assertEquals(['hidden', 'visible'], $c[0]->getHidden());
    }

    public function testMakeVisibleRemovesHiddenFromEntireCollection()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeVisible(['hidden']);

        $this->assertEquals([], $c[0]->getHidden());
    }

    public function testNonModelRelatedMethods()
    {
        $a = new Collection([['foo' => 'bar'], ['foo' => 'baz']]);
        $b = new Collection(['a', 'b', 'c']);
        $this->assertEquals(BaseCollection::class, get_class($a->pluck('foo')));
        $this->assertEquals(BaseCollection::class, get_class($a->keys()));
        $this->assertEquals(BaseCollection::class, get_class($a->collapse()));
        $this->assertEquals(BaseCollection::class, get_class($a->flatten()));
        $this->assertEquals(BaseCollection::class, get_class($a->zip(['a', 'b'], ['c', 'd'])));
        $this->assertEquals(BaseCollection::class, get_class($b->flip()));
    }

    public function testMakeVisibleRemovesHiddenAndIncludesVisible()
    {
        $c = new Collection([new TestEloquentCollectionModel]);
        $c = $c->makeVisible('hidden');

        $this->assertEquals([], $c[0]->getHidden());
        $this->assertEquals(['visible', 'hidden'], $c[0]->getVisible());
    }

    public function testQueueableCollectionImplementation()
    {
        $c = new Collection([new TestEloquentCollectionModel, new TestEloquentCollectionModel]);
        $this->assertEquals(TestEloquentCollectionModel::class, $c->getQueueableClass());
    }

    /**
     * @expectedException \LogicException
     */
    public function testQueueableCollectionImplementationThrowsExceptionOnMultipleModelTypes()
    {
        $c = new Collection([new TestEloquentCollectionModel, (object) ['id' => 'something']]);
        $c->getQueueableClass();
    }
}

class TestEloquentCollectionModel extends \Illuminate\Database\Eloquent\Model
{
    protected $visible = ['visible'];
    protected $hidden = ['hidden'];
}
