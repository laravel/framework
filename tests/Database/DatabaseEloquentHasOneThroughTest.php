<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class DatabaseEloquentHasOneThroughTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);

        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('countries.id', [1, 2]);
        $model1 = new EloquentHasOneThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneThroughModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        // Countries
        $results1 = new EloquentHasOneThroughModelStub;
        $results1->user_id = 1;
        $results2 = new EloquentHasOneThroughModelStub;
        $results2->user_id = 2;

        // Posts
        $model1 = new EloquentHasOneThroughModelStub;
        $model1->user_id = 1;
        $model2 = new EloquentHasOneThroughModelStub;
        $model2->user_id = 2;

        $models = $relation->match([$model1, $model2], new Collection([$results1, $results2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->user_id);
        $this->assertEquals(2, $models[1]->foo->user_id);
    }

    /**
     * Creates a new HasOneThrough relationship of a Post having a Country through a User.
     *
     * @return HasOneThrough
     */
    protected function getRelation()
    {
        list($builder, $country, $user, $farParentKey, $parentKey) = $this->getRelationArguments();

        return new HasOneThrough($builder, $country, $user, $farParentKey, $parentKey);
    }

    protected function getRelationArguments()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('users', 'users.post_id', '=', 'posts.id');
        $builder->shouldReceive('join')->once()->with('countries', 'countries.user_id', '=', 'users.id');
        $builder->shouldReceive('where')->with('countries.id', '=', 1);
        $builder->shouldReceive('whereNotNull')->with('countries.id');

        $country = m::mock('Illuminate\Database\Eloquent\Model');
        $country->shouldReceive('getTable')->andReturn('countries');
        $country->shouldReceive('getQualifiedKeyName')->andReturn('countries.id');
        $country->shouldReceive('getKey')->andReturn(1);
        $country->shouldReceive('getKeyName')->andReturn('id');

        $user = m::mock('Illuminate\Database\Eloquent\Model');
        $user->shouldReceive('getTable')->andReturn('users');
        $user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');

        $post = m::mock('Illuminate\Database\Eloquent\Model');
        $post->shouldReceive('getQualifiedKeyName')->andReturn('posts.id');

        $builder->shouldReceive('getModel')->andReturn($post);

        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $user->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return [$builder, $country, $user, 'user_id', 'post_id'];
    }
}

class EloquentHasOneThroughModelStub extends Illuminate\Database\Eloquent\Model
{
}
