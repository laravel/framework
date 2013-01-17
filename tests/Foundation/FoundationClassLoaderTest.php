<?php

use Mockery as m;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ClassLoader;

class FoundationClassLoaderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testProperFilesAreCheckedByLoader()
	{
		$loader = new ClassLoader(array(__DIR__, __DIR__.'/Models'), $files = m::mock('Illuminate\Filesystem\Filesystem'));
		$files->shouldReceive('exists')->once()->with(__DIR__.'/User'.DIRECTORY_SEPARATOR.'Model.php')->andReturn(false);
		$files->shouldReceive('exists')->once()->with(__DIR__.'/Models/User'.DIRECTORY_SEPARATOR.'Model.php')->andReturn(true);
		$files->shouldReceive('requireOnce')->once()->with(__DIR__.'/Models/User'.DIRECTORY_SEPARATOR.'Model.php');

		$this->assertTrue($loader->load('\\User\\Model'));
	}

}