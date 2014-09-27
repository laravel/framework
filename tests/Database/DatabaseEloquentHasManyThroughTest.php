<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DatabaseEloquentHasManyThroughTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array = []) { return new Collection($array); });
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

		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array) { return new Collection($array); });
		$models = $relation->match([$model1, $model2, $model3], new Collection([$result1, $result2, $result3]), 'foo');

		$this->assertEquals(1, $models[0]->foo[0]->country_id);
		$this->assertEquals(1, count($models[0]->foo));
		$this->assertEquals(2, $models[1]->foo[0]->country_id);
		$this->assertEquals(2, $models[1]->foo[1]->country_id);
		$this->assertEquals(2, count($models[1]->foo));
		$this->assertEquals(0, count($models[2]->foo));
	}


	protected function getRelation()
	{
		$builder = m::mock('Illuminate\Database\Eloquent\Builder');
		$builder->shouldReceive('join')->once()->with('users', 'users.id', '=', 'posts.user_id');
		$builder->shouldReceive('where')->with('users.country_id', '=', 1);

		$country = m::mock('Illuminate\Database\Eloquent\Model');
		$country->shouldReceive('getKey')->andReturn(1);
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
		return new HasManyThrough($builder, $country, $user, 'country_id', 'user_id');
	}

}

class EloquentHasManyThroughModelStub extends Illuminate\Database\Eloquent\Model {
	public $country_id = 'foreign.value';
}
