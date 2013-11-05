<?php

use Mockery as m;
use Illuminate\Database\Console\Migrations\PublishCommand;

class DatabaseMigrationPublishCommandTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->publisher = $this->makePublisher();
		$this->sourcePath = __DIR__ . '/vendor';
		$this->destPath = __DIR__ . '/app/database/migrations';
		$this->command = $this->makeCommand($this->publisher, $this->sourcePath, $this->destPath);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testRunWithNoSourceFiles()
	{
		$this->publisher->shouldReceive('setSourcePath')->once()->with($this->sourcePath . '/foo/bar/src/migrations');
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(false);

		$this->runCommand($this->command, array('package' => 'foo/bar'));
	}


	public function testReceivesSetDestinationPath()
	{
		$this->publisher->shouldReceive('setSourcePath')->once();
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(true);
		$this->publisher->shouldReceive('setDestinationPath')->once()->with($this->destPath);
		$this->publisher->shouldReceive('getSourceFiles')->once()->andReturn(array());

		$this->runCommand($this->command, array('package' => 'foo/bar'));
	}


	public function testInvalidFileNamesAreNotCopied()
	{
		$this->publisher->shouldReceive('setSourcePath')->once();
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(true);
		$this->publisher->shouldReceive('setDestinationPath')->once()->with($this->destPath);
		$files = array('invalid_file_name.php');
		$this->publisher->shouldReceive('getSourceFiles')->once()->andReturn($files);
		$this->publisher->shouldReceive('validMigrationName')->once()->with($files[0])->andReturn(false);

		$this->runCommand($this->command, array('package' => 'foo/bar'));
	}


	public function testDuplicateMigrationsAreNotCopied()
	{
		$this->publisher->shouldReceive('setSourcePath')->once();
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(true);
		$this->publisher->shouldReceive('setDestinationPath')->once()->with($this->destPath);
		$files = array('invalid_file_name.php');
		$this->publisher->shouldReceive('getSourceFiles')->once()->andReturn($files);
		$this->publisher->shouldReceive('validMigrationName')->once()->andReturn(true);
		$this->publisher->shouldReceive('migrationExists')->once()->andReturn(true);

		$this->runCommand($this->command, array('package' => 'foo/bar'));
	}


	public function testInvalidFileNamesAreCopiedWithForceOption()
	{
		$this->publisher->shouldReceive('setSourcePath')->once();
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(true);
		$this->publisher->shouldReceive('setDestinationPath')->once()->with($this->destPath);
		$files = array('invalid_file_name.php');
		$this->publisher->shouldReceive('getSourceFiles')->once()->andReturn($files);
		$this->publisher->shouldReceive('validMigrationName')->once()->with($files[0])->andReturn(false);
		$this->publisher->shouldReceive('migrationExists')->once()->andReturn(false);
		$this->publisher->shouldReceive('publish')->once()->with($files[0]);

		$this->runCommand($this->command, array('package' => 'foo/bar', '--force' => true));
	}


	public function testDuplicateMigrationsAreCopiedWithDuplicateOption()
	{
		$this->publisher->shouldReceive('setSourcePath')->once();
		$this->publisher->shouldReceive('sourceHasMigrations')->once()->andReturn(true);
		$this->publisher->shouldReceive('setDestinationPath')->once()->with($this->destPath);
		$files = array('invalid_file_name.php');
		$this->publisher->shouldReceive('getSourceFiles')->once()->andReturn($files);
		$this->publisher->shouldReceive('validMigrationName')->once()->andReturn(true);
		$this->publisher->shouldReceive('migrationExists')->once()->with($files[0])->andReturn(true);
		$this->publisher->shouldReceive('publish')->once()->with($files[0]);

		$this->runCommand($this->command, array('package' => 'foo/bar', '--duplicate' => true));
	}


	protected function makeCommand($publisher, $sourcePath, $destPath)
	{
		return new DatabaseMigrationPublishCommandTestStub($publisher, $sourcePath, $destPath);
	}


	public function makePublisher()
	{
		return m::mock('Illuminate\Database\Migrations\MigrationPublisher');
	}


	protected function runCommand($command, $input = array())
	{
		return $command->run(new Symfony\Component\Console\Input\ArrayInput($input), new Symfony\Component\Console\Output\NullOutput);
	}

}



class DatabaseMigrationPublishCommandTestStub extends PublishCommand
{
	public function call($command, array $arguments = array())
	{
		//
	}
}