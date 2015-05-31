<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DatabaseSoftDeletingScopeTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testApplyingScopeToABuilder()
	{
		$scope = m::mock(SoftDeletingScope::class.'[extend]');
		$builder = m::mock(Builder::class);
		$model = m::mock(Model::class);
		$model->shouldReceive('getQualifiedDeletedAtColumn')->once()->andReturn('table.deleted_at');
		$builder->shouldReceive('whereNull')->once()->with('table.deleted_at');
		$scope->shouldReceive('extend')->once();

		$scope->apply($builder, $model);
	}


	public function testScopeCanRemoveDeletedAtConstraints()
	{
		$scope = new SoftDeletingScope;
		$builder = m::mock(Builder::class);
		$model = m::mock(Model::class);
		$builder->shouldReceive('getModel')->andReturn($model);
		$model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
		$builder->shouldReceive('getQuery')->andReturn($query = m::mock('StdClass'));
		$query->wheres = [['type' => 'Null', 'column' => 'foo'], ['type' => 'Null', 'column' => 'table.deleted_at']];
		$scope->remove($builder, $model);

		$this->assertEquals($query->wheres, [['type' => 'Null', 'column' => 'foo']]);
	}


	public function testForceDeleteExtension()
	{
		$builder = m::mock(Builder::class);
		$builder->shouldDeferMissing();
		$scope = new SoftDeletingScope;
		$scope->extend($builder);
		$callback = $builder->getMacro('forceDelete');
		$givenBuilder = m::mock(Builder::class);
		$givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock('StdClass'));
		$query->shouldReceive('delete')->once();

		$callback($givenBuilder);
	}


	public function testRestoreExtension()
	{
		$builder = m::mock(Builder::class);
		$builder->shouldDeferMissing();
		$scope = new SoftDeletingScope;
		$scope->extend($builder);
		$callback = $builder->getMacro('restore');
		$givenBuilder = m::mock(Builder::class);
		$givenBuilder->shouldReceive('withTrashed')->once();
		$givenBuilder->shouldReceive('getModel')->once()->andReturn($model = m::mock('StdClass'));
		$model->shouldReceive('getDeletedAtColumn')->once()->andReturn('deleted_at');
		$givenBuilder->shouldReceive('update')->once()->with(['deleted_at' => null]);

		$callback($givenBuilder);
	}


	public function testWithTrashedExtension()
	{
		$builder = m::mock(Builder::class);
		$builder->shouldDeferMissing();
		$scope = m::mock(SoftDeletingScope::class.'[remove]');
		$scope->extend($builder);
		$callback = $builder->getMacro('withTrashed');
		$givenBuilder = m::mock(Builder::class);
		$givenBuilder->shouldReceive('getModel')->andReturn($model = m::mock(Model::class));
		$scope->shouldReceive('remove')->once()->with($givenBuilder, $model);
		$result = $callback($givenBuilder);

		$this->assertEquals($givenBuilder, $result);
	}


	public function testOnlyTrashedExtension()
	{
		$builder = m::mock(Builder::class);
		$builder->shouldDeferMissing();
		$model = m::mock(Model::class);
		$model->shouldDeferMissing();
		$scope = m::mock(SoftDeletingScope::class.'[remove]');
		$scope->extend($builder);
		$callback = $builder->getMacro('onlyTrashed');
		$givenBuilder = m::mock(Builder::class);
		$scope->shouldReceive('remove')->once()->with($givenBuilder, $model);
		$givenBuilder->shouldReceive('getQuery')->andReturn($query = m::mock('StdClass'));
		$givenBuilder->shouldReceive('getModel')->andReturn($model);
		$model->shouldReceive('getQualifiedDeletedAtColumn')->andReturn('table.deleted_at');
		$query->shouldReceive('whereNotNull')->once()->with('table.deleted_at');
		$result = $callback($givenBuilder);

		$this->assertEquals($givenBuilder, $result);
	}

}
