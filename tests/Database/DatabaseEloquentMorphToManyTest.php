<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Tests\App\Models\Relationships\EloquentMorphToManyModelStub;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use SortDirection;

class DatabaseEloquentMorphToManyTest extends TestCase
{
    public function testEagerConstraintsAreProperlyAdded(): void
    {
        $relation = $this->getRelation();
        $relation->getParent()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getParent()->expects('getKeyType')->andReturn('int');
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
        $query = Mockery::mock(QueryBuilder::class);
        $query->expects('from')->with('taggables')->andReturn($query);
        $query->expects('insert')->with([['taggable_id' => 1, 'taggable_type' => get_class($relation->getParent()), 'tag_id' => 2, 'foo' => 'bar']])->andReturn(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->andReturn($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $relation->attach(2, ['foo' => 'bar']);
    }

    public function testDetachRemovesPivotTableRecord(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = Mockery::mock(QueryBuilder::class);
        $query->expects('from')->with('taggables')->andReturn($query);
        $query->expects('where')->with('taggables.taggable_id', 1)->andReturn($query);
        $query->expects('where')->with('taggable_type', get_class($relation->getParent()))->andReturn($query);
        $query->expects('whereIn')->with('taggables.tag_id', [1, 2, 3]);
        $query->expects('delete')->andReturn(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->andReturn($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $this->assertTrue($relation->detach([1, 2, 3]));
    }

    public function testDetachMethodClearsAllPivotRecordsWhenNoIDsAreGiven(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = Mockery::mock(QueryBuilder::class);
        $query->expects('from')->with('taggables')->andReturn($query);
        $query->expects('where')->with('taggables.taggable_id', 1)->andReturn($query);
        $query->expects('where')->with('taggable_type', get_class($relation->getParent()))->andReturn($query);
        $query->shouldReceive('whereIn')->never();
        $query->expects('delete')->andReturn(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->andReturn($query);
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

        $builder->expects('where')->with($column, '=', $value, 'and')->times(2)->andReturnSelf();
        $relation->wherePivot($column, '=', $value);
        $relation->withPivotValue($column, $value);

        $builder->expects('whereBetween')->with($column, [$value, $value], 'and', false)->andReturnSelf();
        $relation->wherePivotBetween($column, [$value, $value]);

        $builder->expects('whereIn')->with($column, [$value], 'and', false)->andReturnSelf();
        $relation->wherePivotIn($column, [$value]);

        $builder->expects('whereNull')->with($column, 'and', false)->andReturnSelf();
        $relation->wherePivotNull($column);

        $builder->expects('orderBy')->with($column, SortDirection::Ascending)->andReturnSelf();
        $relation->orderByPivot($column);
    }

    public function getRelation(): MorphToMany
    {
        [$builder, $parent] = $this->getRelationArguments();

        return new MorphToMany($builder, $parent, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id', 'id');
    }

    public function getRelationArguments(): array
    {
        $parent = Mockery::mock(Model::class);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $parent->shouldReceive('getKey')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $builder = Mockery::mock(Builder::class);
        $related = Mockery::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);

        $related->shouldReceive('getTable')->andReturn('tags');
        $related->shouldReceive('getKeyName')->andReturn('id');
        $related->shouldReceive('qualifyColumn')->with('id')->andReturn('tags.id');
        $related->shouldReceive('getMorphClass')->andReturn(get_class($related));

        $builder->expects('join')->with('taggables', 'tags.id', '=', 'taggables.tag_id');
        $builder->expects('where')->with('taggables.taggable_id', '=', 1);
        $builder->expects('where')->with('taggables.taggable_type', get_class($parent));

        $grammar = Mockery::mock(Grammar::class);
        $grammar->shouldReceive('isExpression')->with(Mockery::type(Expression::class))->andReturnTrue();
        $grammar->shouldReceive('isExpression')->with(Mockery::type('string'))->andReturnFalse();
        $builder->shouldReceive('getQuery')->andReturn(
            Mockery::mock(QueryBuilder::class, ['getGrammar' => $grammar])
        );

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
