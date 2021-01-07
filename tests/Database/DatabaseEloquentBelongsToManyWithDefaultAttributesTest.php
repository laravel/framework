<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseEloquentBelongsToManyWithDefaultAttributesTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testWithPivotValueMethodSetsWhereConditionsForFetching()
    {
        $relation = $this->getMockBuilder(BelongsToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);
    }

    public function testWithPivotValueMethodSetsDefaultArgumentsForInsertion()
    {
        $relation = $this->getMockBuilder(BelongsToMany::class)->onlyMethods(['touchIfTouching'])->setConstructorArgs($this->getRelationArguments())->getMock();
        $relation->withPivotValue(['is_admin' => 1]);

        $query = m::mock(stdClass::class);
        $query->shouldReceive('from')->once()->with('club_user')->andReturn($query);
        $query->shouldReceive('insert')->once()->with([['club_id' => 1, 'user_id' => 1, 'is_admin' => 1]])->andReturn(true);
        $relation->getQuery()->shouldReceive('getQuery')->andReturn($mockQueryBuilder = m::mock(stdClass::class));
        $mockQueryBuilder->shouldReceive('newQuery')->once()->andReturn($query);

        $relation->attach(1);
    }

    public function getRelationArguments()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getKey')->andReturn(1);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
        $parent->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $builder = m::mock(Builder::class);
        $related = m::mock(Model::class);
        $builder->shouldReceive('getModel')->andReturn($related);

        $related->shouldReceive('getTable')->andReturn('users');
        $related->shouldReceive('getKeyName')->andReturn('id');
        $related->shouldReceive('qualifyColumn')->with('id')->andReturn('users.id');

        $builder->shouldReceive('join')->once()->with('club_user', 'users.id', '=', 'club_user.user_id');
        $builder->shouldReceive('where')->once()->with('club_user.club_id', '=', 1);
        $builder->shouldReceive('where')->once()->with('club_user.is_admin', '=', 1, 'and');

        return [$builder, $parent, 'club_user', 'club_id', 'user_id', 'id', 'id', null, false];
    }
}
