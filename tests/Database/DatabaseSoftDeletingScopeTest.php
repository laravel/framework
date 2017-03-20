<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSoftDeletingScopeTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testApplyingScopeToABuilder()
    {
        $scope = m::mock('Illuminate\Database\Eloquent\SoftDeletingScope[extend]');
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getQualifiedDeletedAtColumn')->once()->andReturn('table.deleted_at');
        $builder->shouldReceive('whereNull')->once()->with('table.deleted_at');

        $scope->apply($builder, $model);
    }

    public function testRestoreExtension()
    {
        $builder = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(
            m::mock('Illuminate\Database\ConnectionInterface'),
            m::mock('Illuminate\Database\Query\Grammars\Grammar'),
            m::mock('Illuminate\Database\Query\Processors\Processor')
        ));
        $scope = new \Illuminate\Database\Eloquent\SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restore');
        $givenBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $givenBuilder->shouldReceive('withTrashed')->once();
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock('StdClass'));
        $model->shouldReceive('getDeletedAtColumn')->once()->andReturn('deleted_at');
        $givenBuilder->shouldReceive('update')->once()->with(['deleted_at' => null]);

        $callback($givenBuilder);
    }

    public function testWithTrashedExtension()
    {
        $builder = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(
            m::mock('Illuminate\Database\ConnectionInterface'),
            m::mock('Illuminate\Database\Query\Grammars\Grammar'),
            m::mock('Illuminate\Database\Query\Processors\Processor')
        ));
        $scope = m::mock('Illuminate\Database\Eloquent\SoftDeletingScope[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withTrashed');
        $givenBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $givenBuilder->shouldReceive('getModel')->andReturn($model = m::mock('Illuminate\Database\Eloquent\Model'));
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testOnlyTrashedExtension()
    {
        $builder = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(
            m::mock('Illuminate\Database\ConnectionInterface'),
            m::mock('Illuminate\Database\Query\Grammars\Grammar'),
            m::mock('Illuminate\Database\Query\Processors\Processor')
        ));
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldDeferMissing();
        $scope = m::mock('Illuminate\Database\Eloquent\SoftDeletingScope[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('onlyTrashed');
        $givenBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock('StdClass'));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('whereNotNull')->once()->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testWithoutTrashedExtension()
    {
        $builder = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(
            m::mock('Illuminate\Database\ConnectionInterface'),
            m::mock('Illuminate\Database\Query\Grammars\Grammar'),
            m::mock('Illuminate\Database\Query\Processors\Processor')
        ));
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldDeferMissing();
        $scope = m::mock('Illuminate\Database\Eloquent\SoftDeletingScope[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withoutTrashed');
        $givenBuilder = m::mock('Illuminate\Database\Eloquent\Builder');
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock('stdClass'));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('whereNull')->once()->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }
}
