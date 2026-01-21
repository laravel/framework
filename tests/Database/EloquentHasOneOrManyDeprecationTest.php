<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EloquentHasOneOrManyDeprecationTest extends TestCase
{
    public function testHasManyMatchWithNullLocalKey(): void
    {
        $relation = $this->getHasManyRelation();

        $result1 = new HasOneOrManyDeprecationModelStub;
        $result1->foreign_key = 1;

        $result2 = new HasOneOrManyDeprecationModelStub;
        $result2->foreign_key = '';

        $model1 = new HasOneOrManyDeprecationModelStub;
        $model1->id = 1;
        $model2 = new HasOneOrManyDeprecationModelStub;
        $model2->id = null;

        $relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function ($array) {
            return new Collection($array);
        });

        $models = $relation->match([$model1, $model2], new Collection([$result1, $result2]), 'foo');

        $this->assertCount(1, $models[0]->foo);
        $this->assertNull($models[1]->foo);
    }

    public function testHasOneMatchWithNullLocalKey(): void
    {
        $relation = $this->getHasOneRelation();

        $result1 = new HasOneOrManyDeprecationModelStub;
        $result1->foreign_key = 1;

        $model1 = new HasOneOrManyDeprecationModelStub;
        $model1->id = 1;
        $model2 = new HasOneOrManyDeprecationModelStub;
        $model2->id = null;

        $models = $relation->match([$model1, $model2], new Collection([$result1]), 'foo');

        $this->assertInstanceOf(HasOneOrManyDeprecationModelStub::class, $models[0]->foo);
        $this->assertNull($models[1]->foo);
    }

    protected function getHasManyRelation(): HasMany
    {
        $queryBuilder = m::mock(QueryBuilder::class);
        $builder = m::mock(Builder::class, [$queryBuilder]);
        $builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return new HasMany($builder, $parent, 'table.foreign_key', 'id');
    }

    protected function getHasOneRelation(): HasOne
    {
        $queryBuilder = m::mock(QueryBuilder::class);
        $builder = m::mock(Builder::class, [$queryBuilder]);
        $builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return new HasOne($builder, $parent, 'table.foreign_key', 'id');
    }
}

class HasOneOrManyDeprecationModelStub extends Model
{
    public $foreign_key;
}
