<?php

use Mockery as m;

class DatabaseMigratorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMigrationAreRunUpWhenOutstandingMigrationsExist()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn([
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		]);

		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/2_bar.php');
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/1_foo.php');
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/3_baz.php');

		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn([
			'1_foo',
		]);
		$migrator->getRepository()->shouldReceive('getNextBatchNumber')->once()->andReturn(1);
		$migrator->getRepository()->shouldReceive('log')->once()->with('2_bar', 1);
		$migrator->getRepository()->shouldReceive('log')->once()->with('3_baz', 1);
		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('up')->once();
		$bazMock = m::mock('stdClass');
		$bazMock->shouldReceive('up')->once();
		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('2_bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('3_baz'))->will($this->returnValue($bazMock));

		$migrator->run(__DIR__);
	}


	public function testUpMigrationCanBePretended()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn([
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		]);
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/2_bar.php');
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/1_foo.php');
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/3_baz.php');
		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn([
			'1_foo',
		]);
		$migrator->getRepository()->shouldReceive('getNextBatchNumber')->once()->andReturn(1);

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('getConnection')->once()->andReturn(null);
		$barMock->shouldReceive('up')->once();

		$bazMock = m::mock('stdClass');
		$bazMock->shouldReceive('getConnection')->once()->andReturn(null);
		$bazMock->shouldReceive('up')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('2_bar'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('3_baz'))->will($this->returnValue($bazMock));

		$connection = m::mock('stdClass');
		$connection->shouldReceive('pretend')->with(m::type('Closure'))->andReturnUsing(function($closure)
		{
			$closure();
			return [['query' => 'foo']];
		},
		function($closure)
		{
			$closure();
			return [['query' => 'bar']];
		});
		$resolver->shouldReceive('connection')->with(null)->andReturn($connection);

		$migrator->run(__DIR__, true);
	}


	public function testNothingIsDoneWhenNoMigrationsAreOutstanding()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn([
			__DIR__.'/1_foo.php',
		]);
		$migrator->getFilesystem()->shouldReceive('requireOnce')->with(__DIR__.'/1_foo.php');
		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn([
			'1_foo',
		]);

		$migrator->run(__DIR__);
	}


	public function testLastBatchOfMigrationsCanBeRolledBack()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn([
			$fooMigration = new MigratorTestMigrationStub('foo'),
			$barMigration = new MigratorTestMigrationStub('bar'),
		]);

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('down')->once();

		$fooMock = m::mock('stdClass');
		$fooMock->shouldReceive('down')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('foo'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('bar'))->will($this->returnValue($fooMock));

		$migrator->getRepository()->shouldReceive('delete')->once()->with($barMigration);
		$migrator->getRepository()->shouldReceive('delete')->once()->with($fooMigration);

		$migrator->rollback();
	}


	public function testRollbackMigrationsCanBePretended()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn([
			$fooMigration = new MigratorTestMigrationStub('foo'),
			$barMigration = new MigratorTestMigrationStub('bar'),
		]);

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('getConnection')->once()->andReturn(null);
		$barMock->shouldReceive('down')->once();

		$fooMock = m::mock('stdClass');
		$fooMock->shouldReceive('getConnection')->once()->andReturn(null);
		$fooMock->shouldReceive('down')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('foo'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('bar'))->will($this->returnValue($fooMock));

		$connection = m::mock('stdClass');
		$connection->shouldReceive('pretend')->with(m::type('Closure'))->andReturnUsing(function($closure)
		{
			$closure();
			return [['query' => 'bar']];
		},
		function($closure)
		{
			$closure();
			return [['query' => 'foo']];
		});
		$resolver->shouldReceive('connection')->with(null)->andReturn($connection);

		$migrator->rollback(true);
	}


	public function testNothingIsRolledBackWhenNothingInRepository()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', ['resolve'], [
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		]);
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn([]);

		$migrator->rollback();
	}

}


class MigratorTestMigrationStub {
	public function __construct($migration) { $this->migration = $migration; }
	public $migration;
}
