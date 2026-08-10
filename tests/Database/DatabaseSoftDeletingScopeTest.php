<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSoftDeletingScopeTest extends TestCase
{
    public function testApplyingScopeToABuilder()
    {
        $scope = Mockery::mock(SoftDeletingScope::class.'[extend]');
        $builder = Mockery::mock(EloquentBuilder::class);
        $model = Mockery::mock(Model::class);
        $model->expects('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $builder->expects('whereNull')->with('table.deleted_at');

        $scope->apply($builder, $model);
    }

    public function testRestoreExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));
        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restore');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $givenBuilder->expects('withTrashed');
        $model = Mockery::mock(stdClass::class);
        $givenBuilder->expects('getModel')->andReturn($model);
        $model->expects('getDeletedAtColumn')->andReturn('deleted_at');
        $givenBuilder->expects('update')->with(['deleted_at' => null]);

        $callback($givenBuilder);
    }

    public function testRestoreOrCreateExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('restoreOrCreate');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $givenBuilder->expects('withTrashed');
        $attributes = ['name' => 'foo'];
        $values = ['email' => 'bar'];
        $model = Mockery::mock(Model::class);
        $givenBuilder->expects('firstOrCreate')->with($attributes, $values)->andReturn($model);
        $model->expects('restore')->andReturn(true);
        $result = $callback($givenBuilder, $attributes, $values);

        $this->assertEquals($model, $result);
    }

    public function testCreateOrRestoreExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));

        $scope = new SoftDeletingScope;
        $scope->extend($builder);
        $callback = $builder->getMacro('createOrRestore');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $givenBuilder->expects('withTrashed');
        $attributes = ['name' => 'foo'];
        $values = ['email' => 'bar'];
        $model = Mockery::mock(Model::class);
        $givenBuilder->expects('createOrFirst')->with($attributes, $values)->andReturn($model);
        $model->expects('restore')->andReturn(true);
        $result = $callback($givenBuilder, $attributes, $values);

        $this->assertEquals($model, $result);
    }

    public function testWithTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));
        $scope = Mockery::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withTrashed');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $model = Mockery::mock(Model::class);
        $givenBuilder->expects('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testOnlyTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));
        $model = Mockery::mock(Model::class)->makePartial();
        $scope = Mockery::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('onlyTrashed');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $query = Mockery::mock(stdClass::class);
        $givenBuilder->expects('getModel')->andReturn($model);
        $givenBuilder->expects('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->expects('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->expects('whereNotNull')->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }

    public function testWithoutTrashedExtension()
    {
        $builder = new EloquentBuilder(new BaseBuilder(
            Mockery::mock(ConnectionInterface::class),
            Mockery::mock(Grammar::class),
            Mockery::mock(Processor::class)
        ));
        $model = Mockery::mock(Model::class)->makePartial();
        $scope = Mockery::mock(SoftDeletingScope::class.'[remove]');
        $scope->extend($builder);
        $callback = $builder->getMacro('withoutTrashed');
        $givenBuilder = Mockery::mock(EloquentBuilder::class);
        $query = Mockery::mock(stdClass::class);
        $givenBuilder->expects('getModel')->andReturn($model);
        $givenBuilder->expects('withoutGlobalScope')->with($scope)->andReturn($givenBuilder);
        $model->expects('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
        $givenBuilder->expects('whereNull')->with('table.deleted_at');
        $result = $callback($givenBuilder);

        $this->assertEquals($givenBuilder, $result);
    }
}
