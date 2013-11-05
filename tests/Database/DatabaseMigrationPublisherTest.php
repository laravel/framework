<?php

use Mockery as m;

class DatabaseMigrationPublisherTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->files = m::mock('Illuminate\Filesystem\Filesystem');
		$this->publisher = new Illuminate\Database\Migrations\MigrationPublisher($this->files);
	}


	public function tearDown()
	{
		m::close();
	}


	public function testSourceHasMigrationsReturnsTrueWhenGlobReturnsFiles()
	{
		$this->files->shouldReceive('glob')->once()->with('/foo/bar/*.php')->andReturn(array('baz.php'));
		$this->publisher->setSourcePath('/foo/bar');

		$this->assertTrue($this->publisher->sourceHasMigrations());
	}


	public function testReturnsSourceFilesCorrectly()
	{
		$files = array('baz.php');
		$this->files->shouldReceive('glob')->once()->andReturn($files);
		$this->publisher->setSourcePath('/foo/bar');

		$this->assertEquals($files, $this->publisher->getSourceFiles());
	}


	public function testValidationOfMigrationNames()
	{
		$this->assertTrue($this->publisher->validMigrationName('/foo/bar/2013_11_05_210101_create_something.php'));
		$this->assertTrue($this->publisher->validMigrationName('2013_11_05_210101_create_something.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/2013_11_05_210101_createSomething.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/2013_11_05_21010_create_something.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/2013_11_5_210101_create_something.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/13_11_05_210101_create_something.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/13_11_05_210101_create_something.php'));
		$this->assertFalse($this->publisher->validMigrationName('/foo/bar/2013_11_05_210101_create.something.php'));
	}


	public function testMigrationExistsReturnsTrueWhenInGlob()
	{
		$files = array('2013_11_05_235959_create_something.php');
		$this->files->shouldReceive('glob')->with('/foo/bar/*.php')->once()->andReturn($files);
		
		$this->publisher->setDestinationPath('/foo/bar');
		$this->assertTrue($this->publisher->migrationExists('2013_11_05_000000_create_something.php'));
	}


	public function testMigrationExistsReturnsFalseWhenNotInGlob()
	{
		$files = array('2013_11_05_235959_create_something_else.php');
		$this->files->shouldReceive('glob')->with('/foo/bar/*.php')->once()->andReturn($files);

		$this->publisher->setDestinationPath('/foo/bar');
		$this->assertFalse($this->publisher->migrationExists('2013_11_05_000000_create_something.php'));
	}


	public function testPublishCopiesToCorrectPath()
	{
		$this->files->shouldReceive('glob')->with('/foo/source/*.php')->once()->andReturn(array());
		$this->files->shouldReceive('copy')->with('/foo/source/2013_11_05_211130_something.php', m::on(function($dest) {
			// strip away the timestamp, check the rest of the string instead
			return substr(basename($dest), 17) == '_something.php' && substr($dest, 0, 9) == '/bar/dest';
		}));

		$this->publisher->setSourcePath('/foo/source');
		$this->publisher->setDestinationPath('/bar/dest');
		$this->publisher->publish('/foo/source/2013_11_05_211130_something.php');
	}

}
