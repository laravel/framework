<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentHasOneTest extends TestCase
{
    protected $builder;

    protected $related;

    protected $parent;

    public function testHasOneWithDefault()
    {
        $relation = $this->getRelation()->withDefault();

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithDynamicDefault()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel) {
            $newModel->username = 'taylor';
        });

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithDynamicDefaultUseParentModel()
    {
        $relation = $this->getRelation()->withDefault(function ($newModel, $parentModel) {
            $newModel->username = $parentModel->username;
        });

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testHasOneWithArrayDefault()
    {
        $attributes = ['username' => 'taylor'];

        $relation = $this->getRelation()->withDefault($attributes);

        $this->builder->expects('first')->andReturnNull();

        $newModel = new EloquentHasOneModelStub;

        $this->related->expects('newInstance')->andReturn($newModel);

        $this->assertSame($newModel, $relation->getResults());

        $this->assertSame('taylor', $newModel->username);

        $this->assertSame(1, $newModel->getAttribute('foreign_key'));
    }

    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = Mockery::mock(Model::class);
        $model->expects('setRelation')->with('foo', null);
        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelationWithRealQuery();
        $model1 = new EloquentHasOneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_has_one_model_stubs" where "table"."foreign_key" = ? and "table"."foreign_key" is not null and "table"."foreign_key" in (1, 2)', $relation->toSql());
        $this->assertSame([1], $relation->getBindings());
    }

    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        $result1 = new EloquentHasOneModelStub;
        $result1->foreign_key = 1;
        $result2 = new EloquentHasOneModelStub;
        $result2->foreign_key = 2;
        $result3 = new EloquentHasOneModelStub;
        $result3->foreign_key = new class
        {
            public function __toString()
            {
                return '4';
            }
        };

        $model1 = new EloquentHasOneModelStub;
        $model1->id = 1;
        $model2 = new EloquentHasOneModelStub;
        $model2->id = 2;
        $model3 = new EloquentHasOneModelStub;
        $model3->id = 3;
        $model4 = new EloquentHasOneModelStub;
        $model4->id = 4;

        $models = $relation->match([$model1, $model2, $model3, $model4], new Collection([$result1, $result2, $result3]), 'foo');

        $this->assertEquals(1, $models[0]->foo->foreign_key);
        $this->assertEquals(2, $models[1]->foo->foreign_key);
        $this->assertNull($models[2]->foo);
        $this->assertSame('4', (string) $models[3]->foo->foreign_key);
    }

    public function testRelationCountQueryCanBeBuilt()
    {
        $relation = $this->getRelationWithRealQuery();

        $query = $relation->getRelationExistenceCountQuery($this->newBuilder('one'), $this->newBuilder('two'));

        $this->assertSame('select count(*) from "one" where "eloquent_has_one_model_stubs"."id" = "table"."foreign_key"', $query->toSql());
    }

    protected function newBuilder($table = null)
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = (new Builder(new BaseBuilder($connection, new Grammar($connection), new Processor)))->setModel(new EloquentHasOneModelStub);

        return $table ? $builder->from($table) : $builder;
    }

    protected function getRelationWithRealQuery()
    {
        $parent = new EloquentHasOneModelStub;
        $parent->id = 1;

        return new HasOne($this->newBuilder(), $parent, 'table.foreign_key', 'id');
    }

    protected function getRelation()
    {
        $this->builder = Mockery::mock(Builder::class);
        $this->builder->shouldReceive('whereNotNull')->with('table.foreign_key');
        $this->builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
        $this->related = Mockery::mock(Model::class);
        $this->builder->shouldReceive('getModel')->andReturn($this->related);
        $this->parent = Mockery::mock(Model::class);
        $this->parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $this->parent->shouldReceive('getAttribute')->with('username')->andReturn('taylor');
        $this->parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $this->parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $this->parent->shouldReceive('newQueryWithoutScopes')->andReturn($this->builder);

        return new HasOne($this->builder, $this->parent, 'table.foreign_key', 'id');
    }
}

class EloquentHasOneModelStub extends Model
{
    public $foreign_key = 'foreign.value';
}
