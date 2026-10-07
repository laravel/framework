<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use JMac\Testing\Double;
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
        $query->expects('from')->with('club_user')->returns($query);
        $query->expects('insert')->with([['user_id' => 1, 'club_id' => 1, 'is_admin' => 1]])->returns(true);
        $relation->getQuery()->getQuery()->expects('newQuery')->returns($query);

        $relation->attach(1);
    }

    public function getRelationArguments()
    {
        $parent = Double::for(Model::class);
        $parent->allows('getKey')->returns(1);
        $parent->allows('getCreatedAtColumn')->returns('created_at');
        $parent->allows('getUpdatedAtColumn')->returns('updated_at');
        $parent->allows('getAttribute')->with('id')->returns(1);

        $builder = Double::for(Builder::class);
        $related = Double::for(Model::class);
        $builder->allows('getModel')->returns($related);

        $related->allows('getTable')->returns('users');
        $related->allows('getKeyName')->returns('id');
        $related->allows('qualifyColumn')->with('id')->returns('users.id');

        $builder->expects('join')->with('club_user', 'users.id', '=', 'club_user.user_id');
        $builder->expects('where')->with('club_user.club_id', '=', 1);
        $builder->expects('where')->with('club_user.is_admin', '=', 1, 'and');

        $mockQueryBuilder = Double::for(QueryBuilder::class);
        $builder->allows('getQuery')->returns($mockQueryBuilder);
        $mockQueryBuilder->allows('getGrammar')->returns(new Grammar(Double::for(Connection::class)));

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
