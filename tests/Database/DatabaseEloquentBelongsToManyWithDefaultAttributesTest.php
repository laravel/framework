<?php

namespace Illuminate\Tests\Database;

use JMac\Testing\Double;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Mockery;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithDefaultAttributesTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testWithPivotValueMethodSetsWhereConditionsForFetching()
    {
        $relation = $this->getMockBuilder(BelongsToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWithPivotValueMethodSetsDefaultArgumentsForInsertion()
    {
        $relation = $this->getMockBuilder(BelongsToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);

        $query = Double::for(QueryBuilder::class);
        $query->expects('from')->with('club_user')->andReturn($query);
        $query->expects('insert')->with([['club_id' => 1, 'user_id' => 1, 'is_admin' => 1]])->andReturn(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->andReturn($query);

        $relation->attach(1);
    }

    public function getRelationArguments()
    {
        $parent = Double::for(Model::class);
        $parent->shouldReceive('getKey')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $builder = Double::for(Builder::class);
        $related = Double::for(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);

        $related->shouldReceive('getTable')->andReturn('users');
        $related->shouldReceive('getKeyName')->andReturn('id');
        $related->shouldReceive('qualifyColumn')->with('id')->andReturn('users.id');

        $builder->expects('join')->with('club_user', 'users.id', '=', 'club_user.user_id');
        $builder->expects('where')->with('club_user.club_id', '=', 1);
        $builder->expects('where')->with('club_user.is_admin', '=', 1, 'and');

        $mockQueryBuilder = Double::for(QueryBuilder::class);
        $builder->shouldReceive('getQuery')->andReturn($mockQueryBuilder);
        $mockQueryBuilder->shouldReceive('getGrammar')->andReturn(new Grammar(Double::for(Connection::class)));

        return [
            $builder,
            $parent,
            'club_user',
            'club_id',
            'user_id',
            'id',
            'id',
            null,
            false,
        ];
    }
}
