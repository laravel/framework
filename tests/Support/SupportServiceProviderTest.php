<?php

use Mockery as m;

class SupportServiceProviderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public static function setUpBeforeClass()
	{
		require_once __DIR__.'/stubs/providers/SuperProvider.php';
		require_once __DIR__.'/stubs/providers/SuperSuperProvider.php';
	}


	public function testPackageNameCanBeGuessed()
	{
		$superProvider = new SuperProvider(null);
		$this->assertEquals(realpath(__DIR__.'/'), $superProvider->guessPackagePath());

		$superSuperProvider = new SuperSuperProvider(null);
		$this->assertEquals(realpath(__DIR__.'/'), $superSuperProvider->guessPackagePath());
	}


	public function testPackageWithRegisterComponents()
	{
		$superProvider = m::mock('SuperProvider[addConfigComponent,addLanguageComponent,addViewComponent]', [null]);

		$superProvider->shouldReceive('addConfigComponent')->once()->with('foo/bar', 'foo', __DIR__.'/config')->andReturnNull()
			->shouldReceive('addLanguageComponent')->once()->with('foo/bar', 'foo', __DIR__.'/lang')->andReturnNull()
			->shouldReceive('addViewComponent')->once()->with('foo/bar', 'foo', __DIR__.'/views')->andReturnNull();

		$superProvider->package('foo/bar', 'foo', __DIR__);
	}


	public function testPackageAddConfigComponent()
	{
		$app['config'] = $config = m::mock('\Illuminate\Config\Repository');
		$app['files'] = $files = m::mock('\Illuminate\Filesystem\Filesystem');
		$path = __DIR__.'/config';

		$config->shouldReceive('package')->once()->with('foo/bar', $path, 'foo')->andReturnNull();
		$files->shouldReceive('isDirectory')->once()->with($path)->andReturn(true);

		$superProvider = new SuperProvider($app);

		$superProvider->addConfigComponent('foo/bar', 'foo', $path);
	}


	public function testPackageAddLanguageComponent()
	{
		$app['translator'] = $translator = m::mock('\Illuminate\Translation\Translator');
		$app['files'] = $files = m::mock('\Illuminate\Filesystem\Filesystem');
		$path = __DIR__.'/lang';

		$translator->shouldReceive('addNamespace')->once()->with('foo', $path)->andReturnNull();
		$files->shouldReceive('isDirectory')->once()->with($path)->andReturn(true);

		$superProvider = new SuperProvider($app);

		$superProvider->addLanguageComponent('foo/bar', 'foo', $path);
	}


	public function testPackageAddViewComponent()
	{
		$app['view'] = $view = m::mock('\Illuminate\View\Factory');
		$app['files'] = $files = m::mock('\Illuminate\Filesystem\Filesystem');
		$app['path.base'] = __DIR__;

		$viewAppPath = $app['path.base'].'/resources/views/packages';
		$path = __DIR__.'/views';

		$view->shouldReceive('addNamespace')->once()->with('foo', $viewAppPath.'/foo/bar')->andReturnNull()
			->shouldReceive('addNamespace')->once()->with('foo', $path)->andReturnNull();
		$files->shouldReceive('isDirectory')->once()->with($viewAppPath.'/foo/bar')->andReturn(true)
			->shouldReceive('isDirectory')->once()->with($path)->andReturn(true);

		$superProvider = new SuperProvider($app);

		$superProvider->addViewComponent('foo/bar', 'foo', $path);
	}

}
