<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar;
use Mockery\Adapter\Phpunit\MockeryTestCase as TestCase;
use Mockery as m;
use stdClass;

class DatabaseEloquentMorphToManyTest extends TestCase
{
    public function testEagerConstraintsAreProperlyAdded(): void
    {
        $relation = $this->getRelation();
        $relation->getParent()->shouldReceive('getKeyName')->andReturn('id');
        $relation->getParent()->shouldReceive('getKeyType')->once()->andReturn('int');
        $relation->getQuery()->shouldReceive('whereIntegerInRaw')->once()->with('taggables.taggable_id', [1, 2]);
        $relation->getQuery()->shouldReceive('where')->once()->with('taggables.taggable_type', get_class($relation->getParent()));
        $model1 = new EloquentMorphToManyModelStub;
        $model1->id = 1;
        $model2 = new EloquentMorphToManyModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    public function testAttachInsertsPivotTableRecord(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = m::mock(stdClass::class);
        $query->shouldReceive('from')->once()->with('taggables')->andReturn($query);
        $query->shouldReceive('insert')->once()->with([['taggable_id' => 1, 'taggable_type' => get_class($relation->getParent()), 'tag_id' => 2, 'foo' => 'bar']])->andReturn(true);
        $relation->getQuery()->getQuery()->shouldReceive('newQuery')->once()->andReturn($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $relation->attach(2, ['foo' => 'bar']);
    }

    public function testDetachRemovesPivotTableRecord(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = m::mock(stdClass::class);
        $query->shouldReceive('from')->once()->with('taggables')->andReturn($query);
        $query->shouldReceive('where')->once()->with('taggables.taggable_id', 1)->andReturn($query);
        $query->shouldReceive('where')->once()->with('taggable_type', get_class($relation->getParent()))->andReturn($query);
        $query->shouldReceive('whereIn')->once()->with('taggables.tag_id', [1, 2, 3]);
        $query->shouldReceive('delete')->once()->andReturn(true);
        $relation->getQuery()->getQuery()->shouldReceive('newQuery')->once()->andReturn($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $this->assertTrue($relation->detach([1, 2, 3]));
    }

    public function testDetachMethodClearsAllPivotRecordsWhenNoIDsAreGiven(): void
    {
        $relation = $this->getMockBuilder(MorphToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $query = m::mock(stdClass::class);
        $query->shouldReceive('from')->once()->with('taggables')->andReturn($query);
        $query->shouldReceive('where')->once()->with('taggables.taggable_id', 1)->andReturn($query);
        $query->shouldReceive('where')->once()->with('taggable_type', get_class($relation->getParent()))->andReturn($query);
        $query->shouldReceive('whereIn')->never();
        $query->shouldReceive('delete')->once()->andReturn(true);
        $relation->getQuery()->getQuery()->shouldReceive('newQuery')->once()->andReturn($query);
        $relation->expects($this->once())->method('touchIfTouching');

        $this->assertTrue($relation->detach());
    }

    public function testQueryExpressionCanBePassedToDifferentPivotQueryBuilderClauses(): void
    {
        $value = 'pivot_value';
        $column = new Expression("CONCAT(foo, '_', bar)");
        $relation = $this->getRelation();
        /** @var Builder|m\MockInterface $builder */
        $builder = $relation->getQuery();

        $builder->shouldReceive('where')->with($column, '=', $value, 'and')->times(2)->andReturnSelf();
        $relation->wherePivot($column, '=', $value);
        $relation->withPivotValue($column, $value);

        $builder->shouldReceive('whereBetween')->with($column, [$value, $value], 'and', false)->once()->andReturnSelf();
        $relation->wherePivotBetween($column, [$value, $value]);

        $builder->shouldReceive('whereIn')->with($column, [$value], 'and', false)->once()->andReturnSelf();
        $relation->wherePivotIn($column, [$value]);

        $builder->shouldReceive('whereNull')->with($column, 'and', false)->once()->andReturnSelf();
        $relation->wherePivotNull($column);

        $builder->shouldReceive('orderBy')->with($column, 'asc')->once()->andReturnSelf();
        $relation->orderByPivot($column);
    }

    public function getRelation(): MorphToMany
    {
        [$builder, $parent] = $this->getRelationArguments();

        return new MorphToMany($builder, $parent, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id', 'id');
    }

    public function getRelationArguments(): array
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $parent->shouldReceive('getKey')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $parent->shouldReceive('getMorphClass')->andReturn(get_class($parent));
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $builder = m::mock(Builder::class);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);

        $related->shouldReceive('getTable')->andReturn('tags');
        $related->shouldReceive('getKeyName')->andReturn('id');
        $related->shouldReceive('qualifyColumn')->with('id')->andReturn('tags.id');
        $related->shouldReceive('getMorphClass')->andReturn(get_class($related));

        $builder->shouldReceive('join')->once()->with('taggables', 'tags.id', '=', 'taggables.tag_id');
        $builder->shouldReceive('where')->once()->with('taggables.taggable_id', '=', 1);
        $builder->shouldReceive('where')->once()->with('taggables.taggable_type', get_class($parent));

        $grammar = m::mock(Grammar::class);
        $grammar->shouldReceive('isExpression')->with(m::type(Expression::class))->andReturnTrue();
        $grammar->shouldReceive('isExpression')->with(m::type('string'))->andReturnFalse();
        $builder->shouldReceive('getQuery')->andReturn(
            m::mock(stdClass::class, ['getGrammar' => $grammar])
        );

        return [$builder, $parent, 'taggable', 'taggables', 'taggable_id', 'tag_id', 'id', 'id', 'relation_name', false];
    }
}

class EloquentMorphToManyModelStub extends Model
{
    protected $guarded = [];
}
