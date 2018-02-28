<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentBelongsToManyWithDefaultAttributesTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testwithPivotValueMethodSetsWhereConditionsForFetching()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\BelongsToMany')->setMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);
    }

    public function testwithPivotValueMethodSetsDefaultArgumentsForInsertion()
    {
        $relation = $this->getMockBuilder('Illuminate\Database\Eloquent\Relations\BelongsToMany')->setMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);

        $query = m::mock('stdClass');
        $query->shouldReceive('from')->once()->with('club_user')->andReturn($query);
        $query->shouldReceive('insert')->once()->with([['club_id' => 1, 'user_id' => 1, 'is_admin' => 1]])->andReturn(true);
        $relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock('stdClass'));
        $mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);

        $relation->attach(1);
    }

    public function getRelationArguments()
    {
        $parent = m::mock('Illuminate\Database\Eloquent\Model');
        $parent->shouldReceive('getKey')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $related = m::mock('Illuminate\Database\Eloquent\Model');
        $builder->shouldReceive('getModel')->andReturn($related);

        $related->shouldReceive('getTable')->andReturn('users');
        $related->shouldReceive('getKeyName')->andReturn('id');

        $builder->shouldReceive('join')->once()->with('club_user', 'users.id', '=', 'club_user.user_id');
        $builder->shouldReceive('where')->once()->with('club_user.club_id', '=', 1);
        $builder->shouldReceive('where')->once()->with('club_user.is_admin', '=', 1, 'and');

        return [$builder, $parent, 'club_user', 'club_id', 'user_id', 'id', 'id', null, false];
    }
}
