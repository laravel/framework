<?php

use Mockery as m;

class FoundationConfigPublisherTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPackageConfigPublishing()
	{
		$pub = new Illuminate\Foundation\Publishing\ConfigPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$registrar->shouldReceive('getConfigPath')->once()->andReturn('src/config');
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/config')->andReturn(true);
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/config', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar'));

		$pub = new Illuminate\Foundation\Publishing\ConfigPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), $registrar2 = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$registrar2->shouldReceive('getConfigPath')->once()->andReturn('src/config');
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/config')->andReturn(true);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/config', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar', __DIR__.'/custom-packages'));
	}

	public function testPackageConfigCustomPublishing()
	{
		$pub = new Illuminate\Foundation\Publishing\ConfigPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$registrar->shouldReceive('getConfigPath')->once()->andReturn('config');
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/vendor/foo/bar/config')->andReturn(true);
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/bar/config', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar'));

		$pub = new Illuminate\Foundation\Publishing\ConfigPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), $registrar2 = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$registrar2->shouldReceive('getConfigPath')->once()->andReturn('config');
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/config')->andReturn(true);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/config', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar', __DIR__.'/custom-packages'));
	}

}
