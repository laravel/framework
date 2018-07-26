<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\RollbackCommand;

class DatabaseMigrationRollbackCommandTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRollbackCommandCallsMigratorWithProperArguments()
	{
		$command = new RollbackCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$command->setLaravel(new AppDatabaseMigrationRollbackStub());
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('rollback')->once()->with(false);
		$migrator->shouldReceive('getNotes')->andReturn(array());

		$this->runCommand($command);
	}


	public function testRollbackCommandCanBePretended()
	{
		$command = new RollbackCommand($migrator = m::mock('Illuminate\Database\Migrations\Migrator'));
		$command->setLaravel(new AppDatabaseMigrationRollbackStub());
		$migrator->shouldReceive('setConnection')->once()->with('foo');
		$migrator->shouldReceive('rollback')->once()->with(true);
		$migrator->shouldReceive('getNotes')->andReturn(array());

		$this->runCommand($command, array('--pretend' => true, '--database' => 'foo'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}

class AppDatabaseMigrationRollbackStub {
	public $env = 'development';
	public function environment() { return $this->env; }
}
