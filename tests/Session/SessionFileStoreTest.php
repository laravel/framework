<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionFileStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveSessionReturnsFileWhenItExists()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$files->shouldReceive('exists')->once()->with(__DIR__.'/foo')->andReturn(true);
		$files->shouldReceive('get')->once()->with(__DIR__.'/foo')->andReturn(serialize('hello'));
		$store = new Illuminate\Session\FileStore($files, __DIR__);

		$this->assertEquals('hello', $store->retrieveSession('foo', new Request));
	}


	public function testRetrieveSessionReturnsNullWhenItDoesntExist()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$files->shouldReceive('exists')->once()->with(__DIR__.'/foo')->andReturn(false);
		$store = new Illuminate\Session\FileStore($files, __DIR__);

		$this->assertNull($store->retrieveSession('foo', new Request));
	}


	public function testCreateSessionStoresSessionInProperPath()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$files->shouldReceive('put')->once()->with(__DIR__.'/foo', serialize(array('foo')));
		$store = new Illuminate\Session\FileStore($files, __DIR__);
		$store->createSession('foo', array('foo'), new Response);
	}


	public function testUpdateSessionStoresSessionInProperPath()
	{
		$files = m::mock('Illuminate\Filesystem\Filesystem');
		$files->shouldReceive('put')->once()->with(__DIR__.'/foo', serialize(array('foo')));
		$store = new Illuminate\Session\FileStore($files, __DIR__);
		$store->updateSession('foo', array('foo'), new Response);
	}


	public function testSweepCleansDirectory()
	{
		$mock = m::mock('Illuminate\Filesystem\Filesystem');

		$store = new Illuminate\Session\FileStore($mock, __DIR__);

		$files = array(__DIR__.'/foo.txt', __DIR__.'/bar.txt');

		$mock->shouldReceive('files')->with(__DIR__)->andReturn($files);
		$mock->shouldReceive('lastModified')->with(__DIR__.'/foo.txt')->andReturn(1);
		$mock->shouldReceive('lastModified')->with(__DIR__.'/bar.txt')->andReturn(9999999999);
		$mock->shouldReceive('delete')->with(__DIR__.'/foo.txt');

		$store->sweep(500);
	}

}