<?php

use Mockery as m;

class FoundationAssetPublisherTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicPathPublishing()
	{
		$pub = new Illuminate\Foundation\AssetPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files->shouldReceive('copyDirectory')->once()->with('foo', __DIR__.'/packages/bar')->andReturn(true);

		$this->assertTrue($pub->publish('bar', 'foo'));
	}


	public function testPackageAssetPublishing()
	{
		$pub = new Illuminate\Foundation\AssetPublisher($files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$pub->setPackagePath(__DIR__.'/vendor');
		$files->shouldReceive('copyDirectory')->once()->with(__DIR__.'/vendor/foo/public', __DIR__.'/packages/foo')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo'));

		$pub = new Illuminate\Foundation\AssetPublisher($files2 = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__);
		$files2->shouldReceive('copyDirectory')->once()->with(__DIR__.'/custom-packages/foo/public', __DIR__.'/packages/foo')->andReturn(true);

		$this->assertTrue($pub->publishPackage('foo', __DIR__.'/custom-packages'));
	}

}
