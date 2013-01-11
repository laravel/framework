<?php

use Mockery as m;

class ConfigFileLoaderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testEmptyArrayIsReturnedOnNullPath()
	{
		$loader = $this->getLoader();
		$this->assertEquals(array(), $loader->load('local', 'group', 'namespace'));
	}


	public function testBasicArrayIsReturned()
	{
		$loader = $this->getLoader();
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/app.php')->andReturn(true);
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/local/app.php')->andReturn(false);
		$loader->getFilesystem()->shouldReceive('getRequire')->once()->with(__DIR__.'/app.php')->andReturn(array('foo' => 'bar'));
		$array = $loader->load('local', 'app', null);

		$this->assertEquals(array('foo' => 'bar'), $array);
	}


	public function testEnvironmentArrayIsMerged()
	{
		$loader = $this->getLoader();
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/app.php')->andReturn(true);
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/local/app.php')->andReturn(true);
		$loader->getFilesystem()->shouldReceive('getRequire')->once()->with(__DIR__.'/app.php')->andReturn(array('foo' => 'bar'));
		$loader->getFilesystem()->shouldReceive('getRequire')->once()->with(__DIR__.'/local/app.php')->andReturn(array('foo' => 'blah', 'baz' => 'boom'));
		$array = $loader->load('local', 'app', null);

		$this->assertEquals(array('foo' => 'blah', 'baz' => 'boom'), $array);
	}


	public function testGroupExistsReturnsTrueWhenTheGroupExists()
	{
		$loader = $this->getLoader();
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/app.php')->andReturn(true);
		$this->assertTrue($loader->exists('app'));
	}


	public function testGroupExistsReturnsTrueWhenNamespaceGroupExists()
	{
		$loader = $this->getLoader();
		$loader->addNamespace('namespace', __DIR__.'/namespace');
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/namespace/app.php')->andReturn(true);
		$this->assertTrue($loader->exists('app', 'namespace'));
	}


	public function testGroupExistsReturnsFalseWhenNamespaceHintDoesntExists()
	{
		$loader = $this->getLoader();
		$this->assertFalse($loader->exists('app', 'namespace'));
	}


	public function testGroupExistsReturnsFalseWhenNamespaceGroupDoesntExists()
	{
		$loader = $this->getLoader();
		$loader->addNamespace('namespace', __DIR__.'/namespace');
		$loader->getFilesystem()->shouldReceive('exists')->with(__DIR__.'/namespace/app.php')->andReturn(false);
		$this->assertFalse($loader->exists('app', 'namespace'));
	}


	public function testCascadingPackagesProperlyLoadsFiles()
	{
		$loader = $this->getLoader();
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/packages/dayle/rees/group.php')->andReturn(true);
		$loader->getFilesystem()->shouldReceive('getRequire')->once()->with(__DIR__.'/packages/dayle/rees/group.php')->andReturn(array('bar' => 'baz'));
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/local/packages/dayle/rees/group.php')->andReturn(true);
		$loader->getFilesystem()->shouldReceive('getRequire')->once()->with(__DIR__.'/local/packages/dayle/rees/group.php')->andReturn(array('foo' => 'boom'));
		$items = $loader->cascadePackage('local', 'dayle/rees', 'group', array('foo' => 'bar'));

		$this->assertEquals(array('foo' => 'boom', 'bar' => 'baz'), $items);
	}


	protected function getLoader()
	{
		return new Illuminate\Config\FileLoader(m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
	}

}