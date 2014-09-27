<?php

use Mockery as m;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

class TranslationFileLoaderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testLoadMethodWithoutNamespacesProperlyCallsLoader()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/en/foo.php')->andReturn(true);
		$files->shouldReceive('getRequire')->once()->with(__DIR__.'/en/foo.php')->andReturn(['messages']);

		$this->assertEquals(['messages'], $loader->load('en', 'foo', null));
	}


	public function testLoadMethodWithNamespacesProperlyCallsLoader()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('exists')->once()->with('bar/en/foo.php')->andReturn(true);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/packages/en/namespace/foo.php')->andReturn(false);
		$files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
		$loader->addNamespace('namespace', 'bar');

		$this->assertEquals(['foo' => 'bar'], $loader->load('en', 'foo', 'namespace'));
	}


	public function testLoadMethodWithNamespacesProperlyCallsLoaderAndLoadsLocalOverrides()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('exists')->once()->with('bar/en/foo.php')->andReturn(true);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/packages/en/namespace/foo.php')->andReturn(true);
		$files->shouldReceive('getRequire')->once()->with('bar/en/foo.php')->andReturn(['foo' => 'bar']);
		$files->shouldReceive('getRequire')->once()->with(__DIR__.'/packages/en/namespace/foo.php')->andReturn(['foo' => 'override', 'baz' => 'boom']);
		$loader->addNamespace('namespace', 'bar');

		$this->assertEquals(['foo' => 'override', 'baz' => 'boom'], $loader->load('en', 'foo', 'namespace'));
	}


	public function testEmptyArraysReturnedWhenFilesDontExist()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/en/foo.php')->andReturn(false);
		$files->shouldReceive('getRequire')->never();

		$this->assertEquals([], $loader->load('en', 'foo', null));
	}


	public function testEmptyArraysReturnedWhenFilesDontExistForNamespacedItems()
	{
		$loader = new FileLoader($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('getRequire')->never();

		$this->assertEquals([], $loader->load('en', 'foo', 'bar'));
	}

}
