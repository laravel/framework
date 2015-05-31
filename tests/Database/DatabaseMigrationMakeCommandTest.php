<?php

use Mockery as m;
use Illuminate\Foundation\Composer;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;

class DatabaseMigrationMakeCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testBasicCreateDumpsAutoload()
	{
		$command = new MigrateMakeCommand(
			$creator = m::mock(MigrationCreator::class),
			$composer = m::mock(Composer::class),
			__DIR__.'/vendor'
		);
		$app = new Application;
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/migrations', null, false);
		$composer->shouldReceive('dumpAutoloads')->once();

		$this->runCommand($command, array('name' => 'create_foo'));
	}

	public function testBasicCreateGivesCreatorProperArguments()
	{
		$command = new MigrateMakeCommand(
			$creator = m::mock(MigrationCreator::class),
			m::mock(Composer::class)->shouldIgnoreMissing(),
			__DIR__.'/vendor'
		);
		$app = new Application;
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo'));
	}


	public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
	{
		$command = new MigrateMakeCommand(
			$creator = m::mock(MigrationCreator::class),
			m::mock(Composer::class)->shouldIgnoreMissing(),
			__DIR__.'/vendor'
		);
		$app = new Application;
		$app->useDatabasePath(__DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/migrations', 'users', true);

		$this->runCommand($command, array('name' => 'create_foo', '--create' => 'users'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new ArrayInput($input), new NullOutput);
	}

}
