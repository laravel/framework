<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Database\Console\Migrations\ResetCommand;

class DatabaseMigrationResetCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testResetCommandCallsMigratorWithProperArguments()
	{
		$command = new ResetCommand($migrator = m::mock(Migrator::class));
		$command->setLaravel(new AppDatabaseMigrationStub());
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
		$migrator->shouldReceive('reset')->once()->with(false);
		$migrator->shouldReceive('getNotes')->andReturn([]);

		$this->runCommand($command);
	}


	public function testResetCommandCanBePretended()
	{
		$command = new ResetCommand($migrator = m::mock(Migrator::class));
		$command->setLaravel(new AppDatabaseMigrationStub);
		$migrator->shouldReceive('setConnection')->once()->with('foo');
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(true);
		$migrator->shouldReceive('reset')->once()->with(true);
		$migrator->shouldReceive('getNotes')->andReturn([]);

		$this->runCommand($command, array('--pretend' => true, '--database' => 'foo'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new ArrayInput($input), new NullOutput);
	}

}

class AppDatabaseMigrationStub extends Application {
	public function environment() { return 'development'; }
}
