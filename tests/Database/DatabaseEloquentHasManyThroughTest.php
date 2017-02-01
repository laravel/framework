<?php

namespace Illuminate\Tests\Database;

use stdClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DatabaseEloquentHasManyThroughTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function ($array = []) {
            return new Collection($array);
        });
        $model->shouldReceive('setRelation')->once()->with('foo', m::type('Illuminate\Database\Eloquent\Collection'));
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('users.country_id', [1, 2]);
        $model1 = new EloquentHasManyThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyThroughModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testEagerConstraintsAreProperlyAddedWithCustomKey()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('users', 'users.id', '=', 'posts.user_id');
        $builder->shouldReceive('where')->with('users.country_id', '=', 1);

        $country = m::mock('Illuminate\Database\Eloquent\Model');
        $country->shouldReceive('getKeyName')->andReturn('id');
        $country->shouldReceive('offsetGet')->andReturn(1);
        $country->shouldReceive('getForeignKey')->andReturn('country_id');

        $user = m::mock('Illuminate\Database\Eloquent\Model');
        $user->shouldReceive('getTable')->andReturn('users');
        $user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');
        $post = m::mock('Illuminate\Database\Eloquent\Model');
        $post->shouldReceive('getTable')->andReturn('posts');

        $builder->shouldReceive('getModel')->andReturn($post);

        $relation = new HasManyThrough($builder, $country, $user, 'country_id', 'user_id', 'not_id');
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('users.country_id', [3, 4]);
        $model1 = new EloquentHasManyThroughModelStub;
        $model1->id = 1;
        $model1->not_id = 3;
        $model2 = new EloquentHasManyThroughModelStub;
        $model2->id = 2;
        $model2->not_id = 4;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        $result1 = new EloquentHasManyThroughModelStub;
        $result1->country_id = 1;
        $result2 = new EloquentHasManyThroughModelStub;
        $result2->country_id = 2;
        $result3 = new EloquentHasManyThroughModelStub;
        $result3->country_id = 2;

        $model1 = new EloquentHasManyThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyThroughModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasManyThroughModelStub;
        $model3->id = 3;

        $relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function ($array) {
            return new Collection($array);
        });
        $models = $relation->match([$model1, $model2, $model3], new Collection([$result1, $result2, $result3]), 'foo');

        $this->assertEquals(1, $models[0]->foo[0]->country_id);
        $this->assertEquals(1, count($models[0]->foo));
        $this->assertEquals(2, $models[1]->foo[0]->country_id);
        $this->assertEquals(2, $models[1]->foo[1]->country_id);
        $this->assertEquals(2, count($models[1]->foo));
        $this->assertEquals(0, count($models[2]->foo));
    }

    public function testModelsAreProperlyMatchedToParentsWithNonPrimaryKey()
    {
        $relation = $this->getRelationForNonPrimaryKey();

        $result1 = new EloquentHasManyThroughModelStub;
        $result1->country_id = 1;
        $result2 = new EloquentHasManyThroughModelStub;
        $result2->country_id = 2;
        $result3 = new EloquentHasManyThroughModelStub;
        $result3->country_id = 2;

        $model1 = new EloquentHasManyThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyThroughModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasManyThroughModelStub;
        $model3->id = 3;

        $relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function ($array) {
            return new Collection($array);
        });
        $models = $relation->match([$model1, $model2, $model3], new Collection([$result1, $result2, $result3]), 'foo');

        $this->assertEquals(1, $models[0]->foo[0]->country_id);
        $this->assertEquals(1, count($models[0]->foo));
        $this->assertEquals(2, $models[1]->foo[0]->country_id);
        $this->assertEquals(2, $models[1]->foo[1]->country_id);
        $this->assertEquals(2, count($models[1]->foo));
        $this->assertEquals(0, count($models[2]->foo));
    }

    public function testAllColumnsAreSelectedByDefault()
    {
        $select = ['posts.*', 'users.country_id'];

        $baseBuilder = m::mock('Illuminate\Database\Query\Builder');

        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('newCollection')->once();

        $builder = $relation->getQuery();
        $builder->shouldReceive('applyScopes')->andReturnSelf();
        $builder->shouldReceive('getQuery')->andReturn($baseBuilder);
        $builder->shouldReceive('addSelect')->once()->with($select)->andReturn($builder);
        $builder->shouldReceive('getModels')->once()->andReturn([]);

        $relation->get();
    }

    public function testOnlyProperColumnsAreSelectedIfProvided()
    {
        $select = ['users.country_id'];

        $baseBuilder = m::mock('Illuminate\Database\Query\Builder');
        $baseBuilder->columns = ['foo', 'bar'];

        $relation = $this->getRelation();
        $relation->getRelated()->shouldReceive('newCollection')->once();

        $builder = $relation->getQuery();
        $builder->shouldReceive('applyScopes')->andReturnSelf();
        $builder->shouldReceive('getQuery')->andReturn($baseBuilder);
        $builder->shouldReceive('addSelect')->once()->with($select)->andReturn($builder);
        $builder->shouldReceive('getModels')->once()->andReturn([]);

        $relation->get();
    }

    public function testFirstMethod()
    {
        $relation = m::mock('Illuminate\Database\Eloquent\Relations\HasManyThrough[get]', $this->getRelationArguments());
        $relation->shouldReceive('get')->once()->andReturn(new \Illuminate\Database\Eloquent\Collection(['first', 'second']));
        $relation->shouldReceive('take')->with(1)->once()->andReturn($relation);

        $this->assertEquals('first', $relation->first());
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFailThrowsException()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\HasManyThrough')->setMethods(['find'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->expects($this->once())->method('find')->with('foo')->will($this->returnValue(null));

        try {
            $relation->findOrFail('foo');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertNotEmpty($e->getModel());

            throw $e;
        }
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFirstOrFailThrowsException()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\HasManyThrough')->setMethods(['first'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->expects($this->once())->method('first')->with(['id' => 'foo'])->will($this->returnValue(null));

        try {
            $relation->firstOrFail(['id' => 'foo']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertNotEmpty($e->getModel());

            throw $e;
        }
    }

    public function testFindMethod()
    {
        $returnValue = new StdClass;

        $relation = m::mock('Illuminate\Database\Eloquent\Relations\HasManyThrough[first]', $this->getRelationArguments());
        $relation->shouldReceive('where')->with('posts.id', '=', 'foo')->once()->andReturn($relation);
        $relation->shouldReceive('first')->once()->andReturn($returnValue);

        $related = $relation->getRelated();
        $related->shouldReceive('getQualifiedKeyName')->once()->andReturn('posts.id');

        $this->assertEquals($returnValue, $relation->find('foo'));
    }

    public function testFindManyMethod()
    {
        $returnValue = new \Illuminate\Database\Eloquent\Collection(['first', 'second']);

        $relation = m::mock('Illuminate\Database\Eloquent\Relations\HasManyThrough[get]', $this->getRelationArguments());
        $relation->shouldReceive('get')->once()->andReturn($returnValue);
        $relation->shouldReceive('whereIn')->with('posts.id', ['foo', 'bar'])->once()->andReturn($relation);

        $related = $relation->getRelated();
        $related->shouldReceive('getQualifiedKeyName')->once()->andReturn('posts.id');

        $this->assertEquals($returnValue, $relation->findMany(['foo', 'bar']));
    }

    public function testIgnoreSoftDeletingParent()
    {
        list($builder, $country, , $firstKey, $secondKey) = $this->getRelationArguments();
        $user = new EloquentHasManyThroughSoftDeletingModelStub;

        $builder->shouldReceive('whereNull')->with('users.deleted_at')->once()->andReturn($builder);

        $relation = new HasManyThrough($builder, $country, $user, $firstKey, $secondKey, 'id');
    }

    protected function getRelation()
    {
        list($builder, $country, $user, $firstKey, $secondKey, $overrideKey) = $this->getRelationArguments();

        return new HasManyThrough($builder, $country, $user, $firstKey, $secondKey, $overrideKey);
    }

    protected function getRelationForNonPrimaryKey()
    {
        list($builder, $country, $user, $firstKey, $secondKey, $overrideKey) = $this->getRelationArgumentsForNonPrimaryKey();

        return new HasManyThrough($builder, $country, $user, $firstKey, $secondKey, $overrideKey);
    }

    protected function getRelationArguments()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('users', 'users.id', '=', 'posts.user_id');
        $builder->shouldReceive('where')->with('users.country_id', '=', 1);

        $country = m::mock('Illuminate\Database\Eloquent\Model');
        $country->shouldReceive('getKeyName')->andReturn('id');
        $country->shouldReceive('offsetGet')->andReturn(1);
        $country->shouldReceive('getForeignKey')->andReturn('country_id');
        $user = m::mock('Illuminate\Database\Eloquent\Model');
        $user->shouldReceive('getTable')->andReturn('users');
        $user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');
        $post = m::mock('Illuminate\Database\Eloquent\Model');
        $post->shouldReceive('getTable')->andReturn('posts');

        $builder->shouldReceive('getModel')->andReturn($post);

        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $user->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return [$builder, $country, $user, 'country_id', 'user_id', $country->getKeyName()];
    }

    protected function getRelationArgumentsForNonPrimaryKey()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('users', 'users.id', '=', 'posts.user_id');
        $builder->shouldReceive('where')->with('users.country_id', '=', 1);

        $country = m::mock('Illuminate\Database\Eloquent\Model');
        $country->shouldReceive('offsetGet')->andReturn(1);
        $country->shouldReceive('getForeignKey')->andReturn('country_id');
        $user = m::mock('Illuminate\Database\Eloquent\Model');
        $user->shouldReceive('getTable')->andReturn('users');
        $user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');
        $post = m::mock('Illuminate\Database\Eloquent\Model');
        $post->shouldReceive('getTable')->andReturn('posts');

        $builder->shouldReceive('getModel')->andReturn($post);

        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $user->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return [$builder, $country, $user, 'country_id', 'user_id', 'other_id'];
    }
}

class EloquentHasManyThroughModelStub extends \Illuminate\Database\Eloquent\Model
{
    public $country_id = 'foreign.value';
}

class EloquentHasManyThroughSoftDeletingModelStub extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;
    public $table = 'users';
}
