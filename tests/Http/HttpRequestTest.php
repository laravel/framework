<?php

use Mockery as m;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class HttpRequestTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testInstanceMethod()
	{
		$request = Request::create('', 'GET');
		$this->assertSame($request, $request->instance());
	}


	public function testRootMethod()
	{
		$request = Request::create('http://example.com/foo/bar/script.php?test');
		$this->assertEquals('http://example.com', $request->root());
	}


	public function testPathMethod()
	{
		$request = Request::create('', 'GET');
		$this->assertEquals('/', $request->path());

		$request = Request::create('/foo/bar', 'GET');
		$this->assertEquals('foo/bar', $request->path());
	}


	public function testDecodedPathMethod()
	{
		$request = Request::create('/foo%20bar');
		$this->assertEquals('foo bar', $request->decodedPath());
	}


	/**
	 * @dataProvider segmentProvider
	 */
	public function testSegmentMethod($path, $segment, $expected)
	{
		$request = Request::create($path, 'GET');
		$this->assertEquals($expected, $request->segment($segment, 'default'));
	}


	public function segmentProvider()
	{
		return array(
			array('', 1, 'default'),
			array('foo/bar//baz', '1', 'foo'),
			array('foo/bar//baz', '2', 'bar'),
			array('foo/bar//baz', '3', 'baz'),
		);
	}

	/**
	 * @dataProvider segmentsProvider
	 */
	public function testSegmentsMethod($path, $expected)
	{
		$request = Request::create($path, 'GET');
		$this->assertEquals($expected, $request->segments());

		$request = Request::create('foo/bar', 'GET');
		$this->assertEquals(array('foo', 'bar'), $request->segments());
	}


	public function segmentsProvider()
	{
		return array(
			array('', array()),
			array('foo/bar', array('foo', 'bar')),
			array('foo/bar//baz', array('foo', 'bar', 'baz')),
			array('foo/0/bar', array('foo', '0', 'bar')),
		);
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

		$request = Request::create('https://foo.com', 'GET');
		$this->assertEquals('https://foo.com', $request->fullUrl());
	}


	public function testIsMethod()
	{
		$request = Request::create('/foo/bar', 'GET');

		$this->assertTrue($request->is('foo*'));
		$this->assertFalse($request->is('bar*'));
		$this->assertTrue($request->is('*bar*'));
		$this->assertTrue($request->is('bar*', 'foo*', 'baz'));

		$request = Request::create('/', 'GET');

		$this->assertTrue($request->is('/'));
	}


	public function testAjaxMethod()
	{
		$request = Request::create('/', 'GET');
		$this->assertFalse($request->ajax());
		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'), '{}');
		$this->assertTrue($request->ajax());
	}


	public function testSecureMethod()
	{
		$request = Request::create('http://example.com', 'GET');
		$this->assertFalse($request->secure());
		$request = Request::create('https://example.com', 'GET');
		$this->assertTrue($request->secure());
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

		$request = Request::create('/', 'GET', array('developer' => array('name' => 'Taylor', 'age' => 25)));
		$this->assertEquals(array('developer' => array('age' => 25)), $request->only('developer.age'));
		$this->assertEquals(array('developer' => array('name' => 'Taylor'), 'test' => null), $request->only('developer.name', 'test'));
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
		$all = $request->query(null);
		$this->assertEquals('Taylor', $all['name']);
	}


	public function testCookieMethod()
	{
		$request = Request::create('/', 'GET', array(), array('name' => 'Taylor'));
		$this->assertEquals('Taylor', $request->cookie('name'));
		$this->assertEquals('Bob', $request->cookie('foo', 'Bob'));
		$all = $request->cookie(null);
		$this->assertEquals('Taylor', $all['name']);
	}


	public function testHasCookieMethod()
	{
		$request = Request::create('/', 'GET', array(), array('foo' => 'bar'));
		$this->assertTrue($request->hasCookie('foo'));
		$this->assertFalse($request->hasCookie('qu'));
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
		$all = $request->server(null);
		$this->assertEquals('bar', $all['foo']);
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
		$all = $request->header(null);
		$this->assertEquals('foo', $all['do-this'][0]);
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


	public function testJSONEmulatingPHPBuiltInServer()
	{
		$payload = array('name' => 'taylor');
		$content = json_encode($payload);
		// The built in PHP 5.4 webserver incorrectly provides HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH,
		// rather than CONTENT_TYPE and CONTENT_LENGTH
		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_CONTENT_LENGTH' => strlen($content)), $content);
		$this->assertTrue($request->isJson());
		$data = $request->json()->all();
		$this->assertEquals($payload, $data);

		$data = $request->all();
		$this->assertEquals($payload, $data);
	}


	public function testAllInputReturnsInputAndFiles()
	{
		$file = $this->getMock('Symfony\Component\HttpFoundation\File\UploadedFile', null, array(__FILE__, 'photo.jpg'));
		$request = Request::create('/?boom=breeze', 'GET', array('foo' => 'bar'), array(), array('baz' => $file));
		$this->assertEquals(array('foo' => 'bar', 'baz' => $file, 'boom' => 'breeze'), $request->all());
	}


	public function testAllInputReturnsNestedInputAndFiles()
	{
		$file = $this->getMock('Symfony\Component\HttpFoundation\File\UploadedFile', null, array(__FILE__, 'photo.jpg'));
		$request = Request::create('/?boom=breeze', 'GET', array('foo' => array('bar' => 'baz')), array(), array('foo' => array('photo' => $file)));
		$this->assertEquals(array('foo' => array('bar' => 'baz', 'photo' => $file), 'boom' => 'breeze'), $request->all());
	}


	public function testAllInputReturnsInputAfterReplace()
	{
		$request = Request::create('/?boom=breeze', 'GET', array('foo' => array('bar' => 'baz')));
		$request->replace(array('foo' => array('bar' => 'baz'), 'boom' => 'breeze'));
		$this->assertEquals(array('foo' => array('bar' => 'baz'), 'boom' => 'breeze'), $request->all());
	}


	public function testAllInputWithNumericKeysReturnsInputAfterReplace()
	{
		$request1 = Request::create('/', 'POST', array(0 => 'A', 1 => 'B', 2 => 'C'));
		$request1->replace(array(0 => 'A', 1 => 'B', 2 => 'C'));
		$this->assertEquals(array(0 => 'A', 1 => 'B', 2 => 'C'), $request1->all());

		$request2 = Request::create('/', 'POST', array(1 => 'A', 2 => 'B', 3 => 'C'));
		$request2->replace(array(1 => 'A', 2 => 'B', 3 => 'C'));
		$this->assertEquals(array(1 => 'A', 2 => 'B', 3 => 'C'), $request2->all());
	}


	public function testOldMethodCallsSession()
	{
		$request = Request::create('/', 'GET');
		$session = m::mock('Illuminate\Session\Store');
		$session->shouldReceive('getOldInput')->once()->with('foo', 'bar')->andReturn('boom');
		$request->setSession($session);
		$this->assertEquals('boom', $request->old('foo', 'bar'));
	}


	public function testFlushMethodCallsSession()
	{
		$request = Request::create('/', 'GET');
		$session = m::mock('Illuminate\Session\Store');
		$session->shouldReceive('flashInput')->once();
		$request->setSession($session);
		$request->flush();
	}


	public function testFormatReturnsAcceptableFormat()
	{
		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_ACCEPT' => 'application/json'));
		$this->assertEquals('json', $request->format());
		$this->assertTrue($request->wantsJson());

		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_ACCEPT' => 'application/atom+xml'));
		$this->assertEquals('atom', $request->format());
		$this->assertFalse($request->wantsJson());

		$request = Request::create('/', 'GET', array(), array(), array(), array('HTTP_ACCEPT' => 'is/not/known'));
		$this->assertEquals('html', $request->format());
		$this->assertEquals('foo', $request->format('foo'));
	}


	public function testSessionMethod()
	{
		$this->expectException('RuntimeException');
		$request = Request::create('/', 'GET');
		$request->session();
	}


	public function testCreateFromBase()
	{
		$body = [
			'foo' => 'bar',
			'baz' => ['qux'],
		];

		$server = array(
			'CONTENT_TYPE' => 'application/json',
		);

		$base = SymfonyRequest::create('/', 'GET', array(), array(), array(), $server, json_encode($body));

		$request = Request::createFromBase($base);

		$this->assertEquals($request->request->all(), $body);
	}

}
