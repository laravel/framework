<?php

use Mockery as m;

class DatabaseMigratorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMigrationAreRunUpWhenOutstandingMigrationsExist()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn(array(
			'1_foo',
		));
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
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/2_bar.php',
			__DIR__.'/1_foo.php',
			__DIR__.'/3_baz.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn(array(
			'1_foo',
		));
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
			return array(array('query' => 'foo'));
		},
		function($closure)
		{
			$closure();
			return array(array('query' => 'bar'));
		});
		$resolver->shouldReceive('connection')->with(null)->andReturn($connection);

		$migrator->run(__DIR__, true);	
	}


	public function testNothingIsDoneWhenNoMigrationsAreOutstanding()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/1_foo.php',
		));
		$migrator->getRepository()->shouldReceive('getRan')->once()->andReturn(array(
			'1_foo',
		));

		$migrator->run(__DIR__);
	}


	public function testLastBatchOfMigrationsCanBeRolledBack()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/bar.php',
			__DIR__.'/foo.php',
			__DIR__.'/baz.php',
		));
		$migrator->getRepository()->shouldReceive('getLast')->twice()->andReturn(
			array(
				'oof',
				'rab',
			),
			array(
				'foo',
				'bar',
			)
		);
		$migrator->getRepository()->shouldReceive('getLastBatchNumber')->twice()->andReturn(2);

		$barMock = m::mock('stdClass');
		$barMock->shouldReceive('down')->once();

		$fooMock = m::mock('stdClass');
		$fooMock->shouldReceive('down')->once();

		$migrator->expects($this->at(0))->method('resolve')->with($this->equalTo('foo'))->will($this->returnValue($barMock));
		$migrator->expects($this->at(1))->method('resolve')->with($this->equalTo('bar'))->will($this->returnValue($fooMock));

		$migrator->getRepository()->shouldReceive('delete')->once()->with('foo');
		$migrator->getRepository()->shouldReceive('delete')->once()->with('bar');

		$migrator->rollback(__DIR__);
	}


	public function testRollbackMigrationsCanBePretended()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array(
			__DIR__.'/bar.php',
			__DIR__.'/foo.php',
			__DIR__.'/baz.php',
		));
		$migrator->getRepository()->shouldReceive('getLast')->once()->andReturn(array(
			'foo',
			'bar',
		));
		$migrator->getRepository()->shouldReceive('getLastBatchNumber')->once()->andReturn(2);

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
			return array(array('query' => 'bar'));
		},
		function($closure)
		{
			$closure();
			return array(array('query' => 'foo'));
		});
		$resolver->shouldReceive('connection')->with(null)->andReturn($connection);

		$migrator->rollback(__DIR__, true);
	}


	public function testNothingIsRolledBackWhenNothingInRepository()
	{
		$migrator = $this->getMock('Illuminate\Database\Migrations\Migrator', array('resolve'), array(
			m::mock('Illuminate\Database\Migrations\MigrationRepositoryInterface'),
			$resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'),
			m::mock('Illuminate\Filesystem\Filesystem'),
		));
		$migrator->getFilesystem()->shouldReceive('glob')->once()->with(__DIR__.'/*_*.php')->andReturn(array());
		$migrator->getRepository()->shouldReceive('getLastBatchNumber')->once()->andReturn(0);

		$migrator->rollback(__DIR__);
	}

}


class MigratorTestMigrationStub {
	public function __construct($migration) { $this->migration = $migration; }
	public $migration;
}