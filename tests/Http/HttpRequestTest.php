<?php

use Mockery as m;
use Illuminate\Http\Request;

class HttpRequestTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPathMethod()
	{
		$request = Request::create('', 'GET');
		$this->assertEquals('/', $request->path());

		$request = Request::create('/foo/bar', 'GET');
		$this->assertEquals('foo/bar', $request->path());
	}


	public function testSegmentMethod()
	{
		$request = Request::create('', 'GET');
		$this->assertEquals('default', $request->segment(1, 'default'));

		$request = Request::create('foo/bar', 'GET');
		$this->assertEquals('foo', $request->segment(1, 'default'));
		$this->assertEquals('bar', $request->segment(2, 'default'));
	}


	public function testSegmentsMethod()
	{
		$request = Request::create('', 'GET');
		$this->assertEquals(array(), $request->segments());

		$request = Request::create('foo/bar', 'GET');
		$this->assertEquals(array('foo', 'bar'), $request->segments());
	}


	public function testUrlMethod()
	{
		$request = Request::create('http://foo.com/foo/bar?name=taylor', 'GET');
		$this->assertEquals('http://foo.com/foo/bar', $request->url());

		$request = Request::create('http://foo.com/foo/bar/?', 'GET');
		$this->assertEquals('http://foo.com/foo/bar', $request->url());
	}


	public function testFullUrlMethod()
	{
		$request = Request::create('http://foo.com/foo/bar?name=taylor', 'GET');
		$this->assertEquals('http://foo.com/foo/bar?name=taylor', $request->fullUrl());
	}


	public function testIsMethod()
	{
		$request = Request::create('/foo/bar', 'GET');

		$this->assertTrue($request->is('foo*'));
		$this->assertFalse($request->is('bar*'));
		$this->assertTrue($request->is('*bar*'));

		$request = Request::create('/', 'GET');

		$this->assertTrue($request->is('/'));
	}


	public function testHasMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor'));
		$this->assertTrue($request->has('name'));
		$this->assertFalse($request->has('foo'));
		$this->assertFalse($request->has('name', 'email'));

		$request = Request::create('/', 'GET', array('name' => 'Taylor', 'email' => 'foo'));
		$this->assertTrue($request->has('name'));
		$this->assertTrue($request->has('name', 'email'));

		//test arrays within query string
		$request = Request::create('/', 'GET', array('foo' => array('bar', 'baz')));
		$this->assertTrue($request->has('foo'));
	}


	public function testInputMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor'));
		$this->assertEquals('Taylor', $request->input('name'));
		$this->assertEquals('Bob', $request->input('foo', 'Bob'));
	}


	public function testOnlyMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 25));
		$this->assertEquals(array('age' => 25), $request->only('age'));
		$this->assertEquals(array('name' => 'Taylor', 'age' => 25), $request->only('name', 'age'));
	}


	public function testExceptMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 25));
		$this->assertEquals(array('name' => 'Taylor'), $request->except('age'));
		$this->assertEquals(array(), $request->except('age', 'name'));
	}


	public function testQueryMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor'));
		$this->assertEquals('Taylor', $request->query('name'));
		$this->assertEquals('Bob', $request->query('foo', 'Bob'));
	}


	public function testCookieMethod()
	{
		$request = Request::create('/', 'GET', array(), array('name' => 'Taylor'));
		$this->assertEquals('Taylor', $request->cookie('name'));
		$this->assertEquals('Bob', $request->cookie('foo', 'Bob'));
	}


	public function testFileMethod()
	{
		$files = array(
			'foo' => array(
				'size' => 500,
				'name' => 'foo.jpg',
				'tmp_name' => __FILE__,
				'type' => 'blah',
				'error' => null,
			),
		);
		$request = Request::create('/', 'GET', array(), array(), $files);
		$this->assertInstanceOf('Symfony\Component\HttpFoundation\File\UploadedFile', $request->file('foo'));
	}


	public function testHasFileMethod()
	{
		$request = Request::create('/', 'GET', array(), array(), array());
		$this->assertFalse($request->hasFile('foo'));

		$files = array(
			'foo' => array(
				'size' => 500,
				'name' => 'foo.jpg',
				'tmp_name' => __FILE__,
				'type' => 'blah',
				'error' => null,
			),
		);
		$request = Request::create('/', 'GET', array(), array(), $files);
		$this->assertTrue($request->hasFile('foo'));
	}


	public function testServerMethod()
	{
		$request = Request::create('/', 'GET', array(), array(), array(), array('foo' => 'bar'));
		$this->assertEquals('bar', $request->server('foo'));
		$this->assertEquals('bar', $request->server('foo.doesnt.exist', 'bar'));
	}


	public function testMergeMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor'));
		$merge = array('buddy' => 'Dayle');
		$request->merge($merge);
		$this->assertEquals('Taylor', $request->input('name'));
		$this->assertEquals('Dayle', $request->input('buddy'));
	}


	public function testReplaceMethod()
	{
		$request = Request::create('/', 'GET', array('name' => 'Taylor'));
		$replace = array('buddy' => 'Dayle');
		$request->replace($replace);
		$this->assertNull($request->input('name'));
		$this->assertEquals('Dayle', $request->input('buddy'));
	}


	public function testHeaderMethod()
	{
		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_DO_THIS' => 'foo'));
		$this->assertEquals('foo', $request->header('do-this'));
	}


	public function testJSONMethod()
	{
		$payload = array('name' => 'taylor');
		$request = Request::create('/', 'GET', array(), array(), array(), array('CONTENT_TYPE' => 'application/json'), json_encode($payload));
		$this->assertEquals('taylor', $request->json('name'));
		$this->assertEquals('taylor', $request->input('name'));
		$data = $request->json()->all();
		$this->assertEquals($payload, $data);
	}



	public function testAllInputReturnsInputAndFiles()
	{
		$file = $this->getMock('Symfony\Component\HttpFoundation\File\UploadedFile', null, array(__FILE__, 'photo.jpg'));
		$request = Request::create('/?boom=breeze', 'GET', array('foo' => 'bar'), array(), array('baz' => $file));
		$this->assertEquals(array('foo' => 'bar', 'baz' => $file, 'boom' => 'breeze'), $request->all());
	}


	public function testOldMethodCallsSession()
	{
		$request = Request::create('/', 'GET');
		$session = m::mock('Illuminate\Session\Store');
		$session->shouldReceive('getOldInput')->once()->with('foo', 'bar')->andReturn('boom');
		$request->setSessionStore($session);
		$this->assertEquals('boom', $request->old('foo', 'bar'));
	}

}
