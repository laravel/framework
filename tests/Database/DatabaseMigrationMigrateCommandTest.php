<?php

use Mockery as m;
use Illuminate\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Database\Console\Migrations\MigrateCommand;

class DatabaseMigrationMigrateCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicMigrationsCallMigratorWithProperArguments()
	{
		$command = new MigrateCommand($migrator = m::mock(Migrator::class), __DIR__.'/vendor');
		$app = new ApplicationDatabaseMigrationStub(array('path.database' => __DIR__));
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('run')->once()->with(__DIR__.'/migrations', false);
		$migrator->shouldReceive('getNotes')->andReturn(array());
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

		$this->runCommand($command);
	}


	public function testMigrationRepositoryCreatedWhenNecessary()
	{
		$params = array($migrator = m::mock(Migrator::class), __DIR__.'/vendor');
		$command = $this->getMock(MigrateCommand::class, array('call'), $params);
		$app = new ApplicationDatabaseMigrationStub(array('path.database' => __DIR__));
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('run')->once()->with(__DIR__.'/migrations', false);
		$migrator->shouldReceive('getNotes')->andReturn(array());
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(false);
		$command->expects($this->once())->method('call')->with($this->equalTo('migrate:install'), $this->equalTo(array('--database' => null)));

		$this->runCommand($command);
	}


	public function testTheCommandMayBePretended()
	{
		$command = new MigrateCommand($migrator = m::mock(Migrator::class), __DIR__.'/vendor');
		$app = new ApplicationDatabaseMigrationStub(array('path.database' => __DIR__));
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$migrator->shouldReceive('setConnection')->once()->with(null);
		$migrator->shouldReceive('run')->once()->with(__DIR__.'/migrations', true);
		$migrator->shouldReceive('getNotes')->andReturn(array());
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

		$this->runCommand($command, array('--pretend' => true));
	}


	public function testTheDatabaseMayBeSet()
	{
		$command = new MigrateCommand($migrator = m::mock(Migrator::class), __DIR__.'/vendor');
		$app = new ApplicationDatabaseMigrationStub(array('path.database' => __DIR__));
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$migrator->shouldReceive('setConnection')->once()->with('foo');
		$migrator->shouldReceive('run')->once()->with(__DIR__.'/migrations', false);
		$migrator->shouldReceive('getNotes')->andReturn(array());
		$migrator->shouldReceive('repositoryExists')->once()->andReturn(true);

		$this->runCommand($command, array('--database' => 'foo'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new ArrayInput($input), new NullOutput);
	}

}

class ApplicationDatabaseMigrationStub extends Application {
	public function __construct(array $data = array()) {
		foreach ($data as $abstract => $instance) {
			$this->instance($abstract, $instance);
		}
	}
	public function environment() { return 'development'; }
}
