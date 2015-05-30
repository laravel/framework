<?php

use Mockery as m;

class FoundationProviderRepositoryTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testServicesAreRegisteredWhenManifestIsNotRecompiled()
	{
		$app = m::mock('Illuminate\Foundation\Application')->makePartial();

		$repo = m::mock('Illuminate\Foundation\ProviderRepository[createProvider,loadManifest,shouldRecompile]', array($app, m::mock('Illuminate\Filesystem\Filesystem'), array(__DIR__.'/services.json')));
		$repo->shouldReceive('loadManifest')->once()->andReturn(array('eager' => array('foo'), 'deferred' => array('deferred'), 'providers' => array('providers'), 'when' => array()));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(false);
		$provider = m::mock('Illuminate\Support\ServiceProvider');
		$repo->shouldReceive('createProvider')->once()->with('foo')->andReturn($provider);

		$app->shouldReceive('register')->once()->with($provider);
		$app->shouldReceive('runningInConsole')->andReturn(false);
		$app->shouldReceive('setDeferredServices')->once()->with(array('deferred'));

		$repo->load(array());
	}


	public function testManifestIsProperlyRecompiled()
	{
		$app = m::mock('Illuminate\Foundation\Application');

		$repo = m::mock('Illuminate\Foundation\ProviderRepository[createProvider,loadManifest,writeManifest,shouldRecompile]', array($app, m::mock('Illuminate\Filesystem\Filesystem'), array(__DIR__.'/services.json')));

		$repo->shouldReceive('loadManifest')->once()->andReturn(array('eager' => array(), 'deferred' => array('deferred')));
		$repo->shouldReceive('shouldRecompile')->once()->andReturn(true);

		// foo mock is just a deferred provider
		$repo->shouldReceive('createProvider')->once()->with('foo')->andReturn($fooMock = m::mock('StdClass'));
		$fooMock->shouldReceive('isDeferred')->once()->andReturn(true);
		$fooMock->shouldReceive('provides')->once()->andReturn(array('foo.provides1', 'foo.provides2'));
		$fooMock->shouldReceive('when')->once()->andReturn(array());

		// bar mock is added to eagers since it's not reserved
		$repo->shouldReceive('createProvider')->once()->with('bar')->andReturn($barMock = m::mock('Illuminate\Support\ServiceProvider'));
		$barMock->shouldReceive('isDeferred')->once()->andReturn(false);
		$repo->shouldReceive('writeManifest')->once()->andReturnUsing(function($manifest) { return $manifest; });

		// bar mock should be registered with the application since it's eager
		$repo->shouldReceive('createProvider')->once()->with('bar')->andReturn($barMock);

		$app->shouldReceive('register')->once()->with($barMock);
		$app->shouldReceive('runningInConsole')->andReturn(false);
		$app->shouldReceive('setDeferredServices')->once()->with(array('foo.provides1' => 'foo', 'foo.provides2' => 'foo'));

		$manifest = $repo->load(array('foo', 'bar'));
	}


	public function testShouldRecompileReturnsCorrectValue()
	{
		$repo = new Illuminate\Foundation\ProviderRepository(m::mock('Illuminate\Contracts\Foundation\Application'), new Illuminate\Filesystem\Filesystem, __DIR__.'/services.json');
		$this->assertTrue($repo->shouldRecompile(null, array()));
		$this->assertTrue($repo->shouldRecompile(array('providers' => array('foo')), array('foo', 'bar')));
		$this->assertFalse($repo->shouldRecompile(array('providers' => array('foo')), array('foo')));
	}


	public function testLoadManifestReturnsParsedJSON()
	{
		$repo = new Illuminate\Foundation\ProviderRepository(m::mock('Illuminate\Contracts\Foundation\Application'), $files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__.'/services.json');
		$files->shouldReceive('exists')->once()->with(__DIR__.'/services.json')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/services.json')->andReturn(json_encode($array = array('users' => array('dayle' => true), 'when' => array())));

		$this->assertEquals($array, $repo->loadManifest());
	}


	public function testWriteManifestStoresToProperLocation()
	{
		$repo = new Illuminate\Foundation\ProviderRepository(m::mock('Illuminate\Contracts\Foundation\Application'), $files = m::mock('Illuminate\Filesystem\Filesystem'), __DIR__.'/services.json');
		$files->shouldReceive('put')->once()->with(__DIR__.'/services.json', json_encode(array('foo')));

		$result = $repo->writeManifest(array('foo'));

		$this->assertEquals(array('foo'), $result);
	}

}
