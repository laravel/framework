<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;

class DatabaseMigrationMakeCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicCreateGivesCreatorProperArguments()
	{
		$command = new DatabaseMigrationMakeCommandTestCommandStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = new DatabaseMigrationMakeCommandTestApplicationStub(array('path.database' => __DIR__));
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo'));
	}


	public function testBasicCreateGivesCreatorProperArgumentsWhenTableIsSet()
	{
		$command = new DatabaseMigrationMakeCommandTestCommandStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = new DatabaseMigrationMakeCommandTestApplicationStub(array('path.database' => __DIR__));
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/migrations', 'users', true);

		$this->runCommand($command, array('name' => 'create_foo', '--create' => 'users'));
	}


	public function testPackagePathsMayBeUsed()
	{
		$command = new DatabaseMigrationMakeCommandTestCommandStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$app = new DatabaseMigrationMakeCommandTestApplicationStub(array('path.database' => __DIR__));
		$command->setLaravel($app);
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/vendor/bar/src/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'bar'));
	}


	public function testPackageFallsBackToVendorDirWhenNotExplicit()
	{
		$command = new DatabaseMigrationMakeCommandTestCommandStub($creator = m::mock('Illuminate\Database\Migrations\MigrationCreator'), __DIR__.'/vendor');
		$creator->shouldReceive('create')->once()->with('create_foo', __DIR__.'/vendor/foo/bar/src/migrations', null, false);

		$this->runCommand($command, array('name' => 'create_foo', '--package' => 'foo/bar'));
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}



class DatabaseMigrationMakeCommandTestCommandStub extends MigrateMakeCommand
{
	public function call($command, array $arguments = array())
	{
		//
	}
}



class DatabaseMigrationMakeCommandTestApplicationStub implements ArrayAccess {
	public $content = array();
	public $env = 'development';
	public function __construct(array $data = array()) { $this->content = $data; }
	public function offsetExists($offset) { return isset($this->content[$offset]); }
	public function offsetGet($offset) { return $this->content[$offset]; }
	public function offsetSet($offset, $value) { $this->content[$offset] = $value; }
	public function offsetUnset($offset) { unset($this->content[$offset]); }
	public function environment() { return $this->env; }
	public function call($callback, array $parameters = array(), $defaultMethod = null) { return call_user_func_array($callback, []); }
}
