<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Mockery as m;
use function now;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSoftDeletingScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testApplyingScopeToABuilder()
    {
        Carbon::setTestNow($now = now());

        $scope = m::mock(SoftDeletingScope::class.'[extend]');
        $builder = m::mock(EloquentBuilder::class);
        $model = m::mock(Model::class);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->once()->andReturn('table.deleted_at');
        $builder->shouldReceive('whereNull')->once()->with('table.deleted_at')->andReturn($builder);
        $builder->shouldReceive('orWhere')->once()->withArgs(function ($column, $operator, $datetime) use ($now) {
            $this->assertSame('table.deleted_at', $column);
            $this->assertSame('>', $operator);
            $this->assertEquals($now, $datetime);

            return true;
        });
        $builder->shouldReceive('where')->once()->withArgs(function ($callback) use ($builder) {
            $callback($builder);

            return true;
        });

        $scope->apply($builder, $model);
    }

    public function testRestoreExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restore');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('withTrashed')->once();
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock(stdClass::class));
        $model->shouldReceive('getDeletedAtColumn')->once()->andReturn('deleted_at');
        $givenBuilder->shouldReceive('update')->once()->with(['deleted_at' => null]);

        $callback($givenBuilder);
    }

    public function testWithTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getModel')->andReturn($model = m::mock(Model::class));
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testOnlyTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow($now = now());

        $model = m::mock(Model::class);
        $model->makePartial();
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('onlyTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock(stdClass::class));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('where')->once()->withArgs(function ($column, $operator, $datetime) use ($now) {
            $this->assertSame('table.deleted_at', $column);
            $this->assertSame('<=', $operator);
            $this->assertEquals($now, $datetime);

            return true;
        });
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testWithoutTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow($now = now());

        $model = m::mock(Model::class);
        $model->makePartial();
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withoutTrashed');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock(stdClass::class));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');

        $givenBuilder->shouldReceive('whereNull')->once()->with('table.deleted_at')->andReturn($givenBuilder);
        $givenBuilder->shouldReceive('orWhere')->once()->withArgs(function ($column, $operator, $datetime) use ($now) {
            $this->assertSame('table.deleted_at', $column);
            $this->assertSame('>', $operator);
            $this->assertEquals($now, $datetime);

            return true;
        });
        $givenBuilder->shouldReceive('where')->once()->withArgs(function ($callback) use ($givenBuilder) {
            $callback($givenBuilder);

            return true;
        })->andReturn($givenBuilder);
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testPendingTrashExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow($now = now());

        $model = m::mock(Model::class);
        $model->makePartial();
        $scope = m::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('pendingTrash');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock(stdClass::class));
        $givenBuilder->shouldReceive('getModel')->andReturn($model);
        $givenBuilder->shouldReceive('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->shouldReceive('where')->once()->withArgs(function ($column, $operator, $datetime) use ($now) {
            $this->assertSame('table.deleted_at', $column);
            $this->assertSame('>', $operator);
            $this->assertEquals($now, $datetime);

            return true;
        })->andReturn($givenBuilder);

        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testDeleteAtExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow(now());

        $now = now()->addSecond();

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('trashAt');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('withTrashed')->once()->andReturn($givenBuilder);
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock(stdClass::class));
        $model->shouldReceive('fromDateTime')->once()->with($now)->andReturn($now->format('Y-m-d H:i:s'));
        $model->shouldReceive('getDeletedAtColumn')->once()->andReturn('deleted_at');
        $model->shouldReceive('freshTimestamp')->once()->andReturn(now());
        $givenBuilder->shouldReceive('update')->once()->with(['deleted_at' => $now->format('Y-m-d H:i:s')]);

        $callback($givenBuilder, $now);
    }

    public function testDeleteAtExtensionExceptionIfTimePresent()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The datetime must be set in the future.');

        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow(now());

        $now = now();

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('trashAt');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock(stdClass::class));
        $model->shouldReceive('freshTimestamp')->once()->andReturn(now());
        $model->shouldReceive('fromDateTime')->once()->with($now)->andReturn($now->format('Y-m-d H:i:s'));
        $givenBuilder->shouldNotReceive('withTrashed');
        $givenBuilder->shouldNotReceive('update');

        $callback($givenBuilder, $now);
    }

    public function testDeleteAtExtensionExceptionIfTimePast()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The datetime must be set in the future.');

        $builder = new EloquentBuilder(new BaseBuilder(
            m::mock(ConnectionInterface::class),
            m::mock(Grammar::class),
            m::mock(Processor::class)
        ));

        Carbon::setTestNow(now());

        $now = now()->subSecond();

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('trashAt');
        $givenBuilder = m::mock(EloquentBuilder::class);
        $givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock(stdClass::class));
        $model->shouldReceive('freshTimestamp')->once()->andReturn(now());
        $model->shouldReceive('fromDateTime')->once()->with($now)->andReturn($now->format('Y-m-d H:i:s'));
        $givenBuilder->shouldNotReceive('withTrashed');
        $givenBuilder->shouldNotReceive('update');

        $callback($givenBuilder, $now);
    }
}
