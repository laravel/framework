<?php

namespace Illuminate\Tests\Database;

use Foo\Bar\EloquentModelNamespacedStub;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);
    }

    public function testMorphOneSetsProperConstraints()
    {
        $relation = $this->getRelationWithRealQuery(MorphOne::class);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1], $relation->getBindings());
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $parent = new EloquentMorphResetModelStub;
        $parent->setKeyType('string');
        $relation = $this->getRelationWithRealQuery(MorphOne::class, $parent);

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null and "table"."morph_id" in (?, ?) and "table"."morph_type" = ?', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, '1', 1, 2, EloquentMorphResetModelStub::class], $relation->getBindings());
    }

    /**
     * Note that the tests are the exact same for morph many because the classes share this code...
     * Will still test to be safe.
     */
    public function testMorphManySetsProperConstraints()
    {
        $relation = $this->getRelationWithRealQuery(MorphMany::class);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1], $relation->getBindings());
    }

    public function testMorphManyEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelationWithRealQuery(MorphMany::class);

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);

        $this->assertSame('select * from "eloquent_morph_reset_model_stubs" where "table"."morph_type" = ? and "table"."morph_id" = ? and "table"."morph_id" is not null and "table"."morph_id" in (1, 2) and "table"."morph_type" = ?', $relation->toSql());
        $this->assertSame([EloquentMorphResetModelStub::class, 1, EloquentMorphResetModelStub::class], $relation->getBindings());
    }

    public function testMorphRelationUpsertFillsForeignKey()
    {
        $relation = $this->getManyRelation();

        $relation->getQuery()->expects('upsert')->with(
            [
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
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
                ['email' => 'foo3', 'name' => 'bar', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
                ['name' => 'bar2', 'email' => 'foo2', $relation->getForeignKeyName() => $relation->getParentKey(), $relation->getMorphType() => $relation->getMorphClass()],
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

    protected function getRelationWithRealQuery(string $relation, ?Model $parent = null)
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $builder = (new Builder(new QueryBuilder($connection, new Grammar($connection), new Processor)))->setModel(new EloquentMorphResetModelStub);

        $parent ??= new EloquentMorphResetModelStub;
        $parent->id = 1;

        return new $relation($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getOneRelation()
    {
        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $builder = Mockery::mock(Builder::class, [$queryBuilder]);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->expects('where')->with('table.morph_type', get_class($parent));

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getManyRelation()
    {
        $builder = Mockery::mock(Builder::class);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $builder->expects('where')->with('table.morph_type', get_class($parent));

        return new MorphMany($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }

    protected function getNamespacedRelation($alias)
    {
        require_once __DIR__.'/Fixtures/EloquentModelNamespacedStub.php';

        Relation::morphMap([
            $alias => EloquentModelNamespacedStub::class,
        ]);

        $builder = Mockery::mock(Builder::class);
        $builder->expects('whereNotNull')->with('table.morph_id');
        $builder->expects('where')->with('table.morph_id', '=', 1);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);
        $parent = Mockery::mock(EloquentModelNamespacedStub::class);
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $parent->shouldReceive('getMorphClass')->andReturn($alias);
        $builder->expects('where')->with('table.morph_type', $alias);

        return new MorphOne($builder, $parent, 'table.morph_type', 'table.morph_id', 'id');
    }
}

class EloquentMorphResetModelStub extends Model
{
    //
}
