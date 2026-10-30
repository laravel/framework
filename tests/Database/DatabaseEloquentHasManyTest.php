<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasManyTest extends TestCase
{
    public function testRelationUpsertFillsForeignKey()
    {
        $relation = $this->getRelation();

        $relation->getQuery()->expects('upsert')->with(
            [
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey()],
            ],
            ['email'],
            ['name']
        );

        $relation->upsert(
            ['email' => 'foo3', 'name' => 'bar'],
            ['email'],
            ['name']
        );

        $relation->getQuery()->expects('upsert')->with(
            [
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey()],
                ['name' => 'bar2', 'email' => 'foo2', $relation->getForeignKeyName() => $relation->getParentKey()],
            ],
            ['email'],
            ['name']
        );

        $relation->upsert(
            [
                ['email' => 'foo3', 'name' => 'bar'],
                ['name' => 'bar2', 'email' => 'foo2'],
            ],
            ['email'],
            ['name']
        );
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = new EloquentHasManyModelStub;
        $relation->getRelated()->expects('newCollection')->andReturnUsing(function ($array = []) {
            return new Collection($array);
        });
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
        $this->assertInstanceOf(Collection::class, $model->getRelation('foo'));
        $this->assertCount(0, $model->getRelation('foo'));
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelationWithRealQuery();
        $model1 = new EloquentHasManyModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_has_many_model_stubs" where "table"."foreign_key" = ? and "table"."foreign_key" is not null and "table"."foreign_key" in (1, 2)', $relation->toSql());
        $this->assertSame([1], $relation->getBindings());
    }

    public function testEagerConstraintsAreProperlyAddedWithStringKey()
    {
        $parent = new EloquentHasManyModelStub;
        $parent->setKeyType('string');
        $relation = $this->getRelationWithRealQuery($parent);
        $model1 = new EloquentHasManyModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_has_many_model_stubs" where "table"."foreign_key" = ? and "table"."foreign_key" is not null and "table"."foreign_key" in (?, ?)', $relation->toSql());
        $this->assertSame(['1', 1, 2], $relation->getBindings());
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        $result1 = new EloquentHasManyModelStub;
        $result1->foreign_key = 1;
        $result2 = new EloquentHasManyModelStub;
        $result2->foreign_key = 2;
        $result3 = new EloquentHasManyModelStub;
        $result3->foreign_key = 2;

        $model1 = new EloquentHasManyModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasManyModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasManyModelStub;
        $model3->id = 3;

        $relation->getRelated()->expects('newCollection')->times(2)->andReturnUsing(function ($array) {
            return new Collection($array);
        });
        $models = $relation->match([$model1, $model2, $model3], new Collection([$result1, $result2, $result3]), 'foo');

        $this->assertEquals(1, $models[0]->foo[0]->foreign_key);
        $this->assertCount(1, $models[0]->foo);
        $this->assertEquals(2, $models[1]->foo[0]->foreign_key);
        $this->assertEquals(2, $models[1]->foo[1]->foreign_key);
        $this->assertCount(2, $models[1]->foo);
        $this->assertNull($models[2]->foo);
    }

    protected function getRelationWithRealQuery(?Model $parent = null)
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $query = new QueryBuilder($connection, new Grammar($connection), new Processor);
        $builder = (new Builder($query))->setModel(new EloquentHasManyModelStub);

        $parent ??= new EloquentHasManyModelStub;
        $parent->id = 1;

        return new HasMany($builder, $parent, 'table.foreign_key', 'id');
    }

    protected function getRelation()
    {
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $builder = Mockery::mock(Builder::class, [$queryBuilder]);
        $builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return new HasMany($builder, $parent, 'table.foreign_key', 'id');
    }
}

class EloquentHasManyModelStub extends Model
{
    public $foreign_key = 'foreign.value';
}
