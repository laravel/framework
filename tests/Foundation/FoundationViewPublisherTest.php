<?php

use Mockery as m;

class FoundationViewPublisherTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPackageViewPublishing()
	{
		$pub = new Illuminate\Foundation\ViewPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/views')->andReturn(true);
		$files->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/bar/src/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar'));

		$pub = new Illuminate\Foundation\ViewPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/views')->andReturn(true);
		$files2->shouldReceive('isDirectory')->once()->with(__DIR__.'/packages/foo/bar')->andReturn(true);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/bar/src/views', __DIR__.'/packages/foo/bar')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo/bar', __DIR__.'/custom-packages'));
	}

}
