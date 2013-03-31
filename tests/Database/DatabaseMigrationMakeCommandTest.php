<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\MakeCommand;

class DatabaseMigrationMakeCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCreateGivesCreatorProperArguments()
	{
		$command = new DatabaseMigrationMakeCommandTestStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = array('path' => __DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/database/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo'));
	}


	public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
	{
		$command = new DatabaseMigrationMakeCommandTestStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = array('path' => __DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/database/migrations', 'users', true);

		$this->runCommand($command, array('name' => 'create_foo', '--table' => 'users', '--create' => true));
	}


	public function testPackagePathsMayBeUsed()
	{
		$command = new DatabaseMigrationMakeCommandTestStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = array('path' => __DIR__);
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/vendor/bar/src/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'bar'));
	}


	public function testPackageFallsBackToVendorDirWhenNotExplicit()
	{
		$command = new DatabaseMigrationMakeCommandTestStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/vendor/foo/bar/src/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'foo/bar'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}



class DatabaseMigrationMakeCommandTestStub extends MakeCommand
{
	public function call($command, array $arguments = array())
	{
		//
	}
}