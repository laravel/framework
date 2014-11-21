<?php

use Mockery as m;

class FoundationViewPublisherTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	public function testPackageViewPublishing()
	{
		$pub = new Illuminate\Foundation\Publishing\ViewPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$registrar->shouldReceive('getViewsPath')->once()->with('foo/bar')->andReturn('src/views');
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/views')->andReturn(true);
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar'));

		$pub = new Illuminate\Foundation\Publishing\ViewPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$registrar->shouldReceive('getViewsPath')->once()->with('foo/bar')->andReturn('src/views');
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/views')->andReturn(true);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar', __DIR__.'/custom-packages'));
	}

	public function testPackageViewCustomPublishing()
	{
		$pub = new Illuminate\Foundation\Publishing\ViewPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$registrar->shouldReceive('getViewsPath')->once()->with('foo/bar')->andReturn('resources/views');
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/vendor/foo/bar/resources/views')->andReturn(true);
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/bar/resources/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar'));

		$pub = new Illuminate\Foundation\Publishing\ViewPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), $registrar = m::mock('Illuminate\Foundation\Publishing\PackageRegistrar'), __DIR__);
		$registrar->shouldReceive('getViewsPath')->once()->with('foo/bar')->andReturn('resources/views');
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/resources/views')->andReturn(true);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/resources/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar', __DIR__.'/custom-packages'));
	}

}
