<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use SortDirection;

class DatabaseEloquentMorphToManyTest extends TestCase
{
    public function testEagerConstraintsAreProperlyAdded(): void
    {
        $relation = $this->getRelation();
        $relation->getParent()->allows('getKeyName')->returns('id');
        $relation->getParent()->expects('getKeyType')->returns('int');
        $relation->getQuery()->expects('whereIntegerInRaw')->with('taggables.taggable_id', [1, 2]);
        $relation->getQuery()->expects('where')->with('taggables.taggable_type', get_class($relation->getParent()));
        $model1 = new EloquentMorphToManyModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphToManyModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testAttachInsertsPivotTableRecord(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = Double::for(QueryBuilder::class);
        $query->expects('from')->with('taggables')->returns($query);
        $query->expects('insert')->with([['taggable_id' => 1, 'taggable_type' => get_class($relation->getParent()), 'tag_id' => 2, 'foo' => 'bar']])->returns(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->returns($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $relation->attach(2, ['foo' => 'bar']);
    }

    public function testDetachRemovesPivotTableRecord(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = Double::for(QueryBuilder::class);
        $query->expects('from')->with('taggables')->returns($query);
        $query->expects('where')->with('taggables.taggable_id', 1)->returns($query);
        $query->expects('where')->with('taggable_type', get_class($relation->getParent()))->returns($query);
        $query->expects('whereIn')->with('taggables.tag_id', [1, 2, 3]);
        $query->expects('delete')->returns(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->returns($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $this->assertTrue($relation->detach([1, 2, 3]));
    }

    public function testDetachMethodClearsAllPivotRecordsWhenNoIDsAreGiven(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = Double::for(QueryBuilder::class);
        $query->expects('from')->with('taggables')->returns($query);
        $query->expects('where')->with('taggables.taggable_id', 1)->returns($query);
        $query->expects('where')->with('taggable_type', get_class($relation->getParent()))->returns($query);
        $query->expects('whereIn')->never();
        $query->expects('delete')->returns(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->returns($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $this->assertTrue($relation->detach());
    }

    public function testQueryExpressionCanBePassedToDifferentPivotQueryBuilderClauses(): void
    {
        $value = 'pivot_value';
        $column = new Expression("CONCAT(foo, '_', bar)");
        $relation = $this->getRelation();
        /** @var Builder|Mockery\MockInterface $builder */
        $builder = $relation->getQuery();

        $builder->expects('where')->with($column, '=', $value, 'and')->times(2)->returns($builder);
        $relation->wherePivot($column, '=', $value);
        $relation->withPivotValue($column, $value);

        $builder->expects('whereBetween')->with($column, [$value, $value], 'and', false)->returns($builder);
        $relation->wherePivotBetween($column, [$value, $value]);

        $builder->expects('whereIn')->with($column, [$value], 'and', false)->returns($builder);
        $relation->wherePivotIn($column, [$value]);

        $builder->expects('whereNull')->with($column, 'and', false)->returns($builder);
        $relation->wherePivotNull($column);

        $builder->expects('orderBy')->with($column, SortDirection::Ascending)->returns($builder);
        $relation->orderByPivot($column);
    }

    public function getRelation(): MorphToMany
    {
        [$builder, $parent] = $this->getRelationArguments();

        return new MorphToMany($builder, $parent, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id', 'id');
    }

    public function getRelationArguments(): array
    {
        $parent = Double::for(Model::class);
        $parent->allows('getMorphClass')->returns(get_class($parent));
        $parent->allows('getKey')->returns(1);
        $parent->allows('getCreatedAtColumn')->returns('created_at');
        $parent->allows('getUpdatedAtColumn')->returns('updated_at');
        $parent->allows('getMorphClass')->returns(get_class($parent));
        $parent->allows('getAttribute')->with('id')->returns(1);

        $builder = Double::for(Builder::class);
        $related = Double::for(Model::class);
        $builder->allows('getModel')->returns($related);

        $related->allows('getTable')->returns('tags');
        $related->allows('getKeyName')->returns('id');
        $related->allows('qualifyColumn')->with('id')->returns('tags.id');
        $related->allows('getMorphClass')->returns(get_class($related));

        $builder->expects('join')->with('taggables', 'tags.id', '=', 'taggables.tag_id');
        $builder->expects('where')->with('taggables.taggable_id', '=', 1);
        $builder->expects('where')->with('taggables.taggable_type', get_class($parent));

        $grammar = new Grammar(Double::for(Connection::class));
        $builder->allows('getQuery')->returns(Mockery::mock(QueryBuilder::class, ['getGrammar' => $grammar]));

        return [
            $builder,
            $parent,
            'taggable',
            'taggables',
            'taggable_id',
            'tag_id',
            'id',
            'id',
            'relation_name',
            false,
        ];
    }
}

class EloquentMorphToManyModelStub extends Model
{
    protected $guarded = [];
}
