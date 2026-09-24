<?php

namespace Illuminate\Tests\Database;

use Foo\Bar\EloquentModelNamespacedStub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMorphTest extends TestCase
{
    protected function tearDown(): void
    {
        Relation::morphMap([], false);
    }

    public function testMorphOneSetsProperConstraints()
    {
        $this->getOneRelation();
    }

    public function testMorphOneEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getOneRelation();
        $relation->getParent()->expects('getKeyName')->andReturn('id');
        $relation->getParent()->expects('getKeyType')->andReturn('string');
        $relation->getQuery()->expects('whereIn')->with('table.morph_id', [1, 2]);
        $relation->getQuery()->expects('where')->with('table.morph_type', get_class($relation->getParent()));

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    /**
     * Note that the tests are the exact same for morph many because the classes share this code...
     * Will still test to be safe.
     */
    public function testMorphManySetsProperConstraints()
    {
        $this->getManyRelation();
    }

    public function testMorphManyEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getManyRelation();
        $relation->getParent()->expects('getKeyName')->andReturn('id');
        $relation->getParent()->expects('getKeyType')->andReturn('int');
        $relation->getQuery()->expects('whereIntegerInRaw')->with('table.morph_id', [1, 2]);
        $relation->getQuery()->expects('where')->with('table.morph_type', get_class($relation->getParent()));

        $model1 = new EloquentMorphResetModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphResetModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
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
