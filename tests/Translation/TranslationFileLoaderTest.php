<?php

use Mockery as m;
use Illuminate\Filesystem\FileNotFoundException;
use Illuminate\Translation\FileLoader;

class TranslationFileLoaderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testLoadMethodWithoutNamespacesProperlyCallsLoader()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->once()->with(__DIR__.'/en/foo.php')->andReturn(array('messages'));

		$this->assertEquals(array('messages'), $loader->load('en', 'foo', null));
	}


	public function testLoadMethodWithNamespacesProperlyCallsLoader()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->once()->with(__DIR__.'/packages/en/namespace/foo.php')->andThrow(new FileNotFoundException);
		$files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(array('foo' => 'bar'));
		$loader->addNamespace('namespace', 'bar');

		$this->assertEquals(array('foo' => 'bar'), $loader->load('en', 'foo', 'namespace'));
	}


	public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverrides()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(array('foo' => 'bar'));
		$files->shouldReceive('getRequire')->once()->with(__DIR__.'/packages/en/namespace/foo.php')->andReturn(array('foo' => 'override', 'baz' => 'boom'));
		$loader->addNamespace('namespace', 'bar');

		$this->assertEquals(array('foo' => 'override', 'baz' => 'boom'), $loader->load('en', 'foo', 'namespace'));
	}


	public function testEmptyArraysReturnedWhenFilesDontExist()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->andThrow(new FileNotFoundException);

		$this->assertEquals(array(), $loader->load('en', 'foo', null));
	}


	public function testEmptyArraysReturnedWhenFilesDontExistForNamespacedItems()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->never();

		$this->assertEquals(array(), $loader->load('en', 'foo', 'bar'));
	}

}
