<?php

namespace Illuminate\Tests\Http;

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class HttpRequestTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testInstanceMethod()
    {
        $request = Request::create('', 'GET');
        $this->assertSame($request, $request->instance());
    }

    public function testMethodMethod()
    {
        $request = Request::create('', 'GET');
        $this->assertSame('GET', $request->method());

        $request = Request::create('', 'HEAD');
        $this->assertSame('HEAD', $request->method());

        $request = Request::create('', 'POST');
        $this->assertSame('POST', $request->method());

        $request = Request::create('', 'PUT');
        $this->assertSame('PUT', $request->method());

        $request = Request::create('', 'PATCH');
        $this->assertSame('PATCH', $request->method());

        $request = Request::create('', 'DELETE');
        $this->assertSame('DELETE', $request->method());

        $request = Request::create('', 'OPTIONS');
        $this->assertSame('OPTIONS', $request->method());
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
        return [
            ['', 1, 'default'],
            ['foo/bar//baz', '1', 'foo'],
            ['foo/bar//baz', '2', 'bar'],
            ['foo/bar//baz', '3', 'baz'],
        ];
    }

    /**
     * @dataProvider segmentsProvider
     */
    public function testSegmentsMethod($path, $expected)
    {
        $request = Request::create($path, 'GET');
        $this->assertEquals($expected, $request->segments());

        $request = Request::create('foo/bar', 'GET');
        $this->assertEquals(['foo', 'bar'], $request->segments());
    }

    public function segmentsProvider()
    {
        return [
            ['', []],
            ['foo/bar', ['foo', 'bar']],
            ['foo/bar//baz', ['foo', 'bar', 'baz']],
            ['foo/0/bar', ['foo', '0', 'bar']],
        ];
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

        $request = Request::create('https://foo.com', 'GET');
        $this->assertEquals('https://foo.com/?coupon=foo', $request->fullUrlWithQuery(['coupon' => 'foo']));

        $request = Request::create('https://foo.com?a=b', 'GET');
        $this->assertEquals('https://foo.com/?a=b', $request->fullUrl());

        $request = Request::create('https://foo.com?a=b', 'GET');
        $this->assertEquals('https://foo.com/?a=b&coupon=foo', $request->fullUrlWithQuery(['coupon' => 'foo']));

        $request = Request::create('https://foo.com?a=b', 'GET');
        $this->assertEquals('https://foo.com/?a=c', $request->fullUrlWithQuery(['a' => 'c']));

        $request = Request::create('http://foo.com/foo/bar?name=taylor', 'GET');
        $this->assertEquals('http://foo.com/foo/bar?name=taylor', $request->fullUrlWithQuery(['name' => 'taylor']));

        $request = Request::create('http://foo.com/foo/bar/?name=taylor', 'GET');
        $this->assertEquals('http://foo.com/foo/bar?name=graham', $request->fullUrlWithQuery(['name' => 'graham']));
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

    public function testRouteIsMethod()
    {
        $request = Request::create('/foo/bar', 'GET');

        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });

        $this->assertTrue($request->routeIs('foo.bar'));
        $this->assertFalse($request->routeIs('foo.foo'));
    }

    public function testAjaxMethod()
    {
        $request = Request::create('/', 'GET');
        $this->assertFalse($request->ajax());
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], '{}');
        $this->assertTrue($request->ajax());
        $request = Request::create('/', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue($request->ajax());
        $request->headers->set('X-Requested-With', '');
        $this->assertFalse($request->ajax());
    }

    public function testPjaxMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_X_PJAX' => 'true'], '{}');
        $this->assertTrue($request->pjax());
        $request->headers->set('X-PJAX', 'false');
        $this->assertTrue($request->pjax());
        $request->headers->set('X-PJAX', null);
        $this->assertFalse($request->pjax());
        $request->headers->set('X-PJAX', '');
        $this->assertFalse($request->pjax());
    }

    public function testSecureMethod()
    {
        $request = Request::create('http://example.com', 'GET');
        $this->assertFalse($request->secure());
        $request = Request::create('https://example.com', 'GET');
        $this->assertTrue($request->secure());
    }

    public function testExistsMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertTrue($request->exists('name'));
        $this->assertFalse($request->exists('foo'));
        $this->assertFalse($request->exists('name', 'email'));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($request->exists('name'));
        $this->assertTrue($request->exists('name', 'email'));

        $request = Request::create('/', 'GET', ['foo' => ['bar', 'bar']]);
        $this->assertTrue($request->exists('foo'));

        $request = Request::create('/', 'GET', ['foo' => '', 'bar' => null]);
        $this->assertTrue($request->exists('foo'));
        $this->assertTrue($request->exists('bar'));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertTrue($request->exists('foo.bar'));
        $this->assertTrue($request->exists('foo.baz'));
    }

    public function testHasMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertTrue($request->has('name'));
        $this->assertFalse($request->has('foo'));
        $this->assertFalse($request->has('name', 'email'));

        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $this->assertTrue($request->has('name'));
        $this->assertTrue($request->has('name', 'email'));

        //test arrays within query string
        $request = Request::create('/', 'GET', ['foo' => ['bar', 'baz']]);
        $this->assertTrue($request->has('foo'));

        $request = Request::create('/', 'GET', ['foo' => ['bar' => 'baz']]);
        $this->assertTrue($request->has('foo.bar'));
    }

    public function testInputMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertEquals('Taylor', $request->input('name'));
        $this->assertEquals('Taylor', $request['name']);
        $this->assertEquals('Bob', $request->input('foo', 'Bob'));

        $request = Request::create('/', 'GET', [], [], ['file' => new \Symfony\Component\HttpFoundation\File\UploadedFile(__FILE__, 'foo.php')]);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\File\UploadedFile', $request['file']);
    }

    public function testOnlyMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 25]);
        $this->assertEquals(['age' => 25], $request->only('age'));
        $this->assertEquals(['name' => 'Taylor', 'age' => 25], $request->only('name', 'age'));

        $request = Request::create('/', 'GET', ['developer' => ['name' => 'Taylor', 'age' => 25]]);
        $this->assertEquals(['developer' => ['age' => 25]], $request->only('developer.age'));
        $this->assertEquals(['developer' => ['name' => 'Taylor'], 'test' => null], $request->only('developer.name', 'test'));
    }

    public function testExceptMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 25]);
        $this->assertEquals(['name' => 'Taylor'], $request->except('age'));
        $this->assertEquals([], $request->except('age', 'name'));
    }

    public function testIntersectMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'age' => null]);
        $this->assertEquals(['name' => 'Taylor'], $request->intersect('name', 'age', 'email'));
    }

    public function testQueryMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $this->assertEquals('Taylor', $request->query('name'));
        $this->assertEquals('Bob', $request->query('foo', 'Bob'));
        $all = $request->query(null);
        $this->assertEquals('Taylor', $all['name']);
    }

    public function testCookieMethod()
    {
        $request = Request::create('/', 'GET', [], ['name' => 'Taylor']);
        $this->assertEquals('Taylor', $request->cookie('name'));
        $this->assertEquals('Bob', $request->cookie('foo', 'Bob'));
        $all = $request->cookie(null);
        $this->assertEquals('Taylor', $all['name']);
    }

    public function testHasCookieMethod()
    {
        $request = Request::create('/', 'GET', [], ['foo' => 'bar']);
        $this->assertTrue($request->hasCookie('foo'));
        $this->assertFalse($request->hasCookie('qu'));
    }

    public function testFileMethod()
    {
        $files = [
            'foo' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__,
                'type' => 'blah',
                'error' => null,
            ],
        ];
        $request = Request::create('/', 'GET', [], [], $files);
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\File\UploadedFile', $request->file('foo'));
    }

    public function testHasFileMethod()
    {
        $request = Request::create('/', 'GET', [], [], []);
        $this->assertFalse($request->hasFile('foo'));

        $files = [
            'foo' => [
                'size' => 500,
                'name' => 'foo.jpg',
                'tmp_name' => __FILE__,
                'type' => 'blah',
                'error' => null,
            ],
        ];
        $request = Request::create('/', 'GET', [], [], $files);
        $this->assertTrue($request->hasFile('foo'));
    }

    public function testServerMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['foo' => 'bar']);
        $this->assertEquals('bar', $request->server('foo'));
        $this->assertEquals('bar', $request->server('foo.doesnt.exist', 'bar'));
        $all = $request->server(null);
        $this->assertEquals('bar', $all['foo']);
    }

    public function testMergeMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $merge = ['buddy' => 'Dayle'];
        $request->merge($merge);
        $this->assertEquals('Taylor', $request->input('name'));
        $this->assertEquals('Dayle', $request->input('buddy'));
    }

    public function testReplaceMethod()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);
        $replace = ['buddy' => 'Dayle'];
        $request->replace($replace);
        $this->assertNull($request->input('name'));
        $this->assertEquals('Dayle', $request->input('buddy'));
    }

    public function testHeaderMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_DO_THIS' => 'foo']);
        $this->assertEquals('foo', $request->header('do-this'));
        $all = $request->header(null);
        $this->assertEquals('foo', $all['do-this'][0]);
    }

    public function testJSONMethod()
    {
        $payload = ['name' => 'taylor'];
        $request = Request::create('/', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
        $this->assertEquals('taylor', $request->json('name'));
        $this->assertEquals('taylor', $request->input('name'));
        $data = $request->json()->all();
        $this->assertEquals($payload, $data);
    }

    public function testJSONEmulatingPHPBuiltInServer()
    {
        $payload = ['name' => 'taylor'];
        $content = json_encode($payload);
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_CONTENT_TYPE' => 'application/json', 'HTTP_CONTENT_LENGTH' => strlen($content)], $content);
        $this->assertTrue($request->isJson());
        $data = $request->json()->all();
        $this->assertEquals($payload, $data);

        $data = $request->all();
        $this->assertEquals($payload, $data);
    }

    public function testPrefers()
    {
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json'])->prefers(['json']));
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json'])->prefers(['html', 'json']));
        $this->assertEquals('application/foo+json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/foo+json'])->prefers('application/foo+json'));
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/foo+json'])->prefers('json'));
        $this->assertEquals('html', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json;q=0.5, text/html;q=1.0'])->prefers(['json', 'html']));
        $this->assertEquals('txt', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json;q=0.5, text/plain;q=1.0, text/html;q=1.0'])->prefers(['json', 'txt', 'html']));
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*'])->prefers('json'));
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8'])->prefers('json'));
        $this->assertNull(Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/xml; charset=utf-8'])->prefers(['html', 'json']));
        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json, text/html'])->prefers(['html', 'json']));
        $this->assertEquals('html', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json;q=0.4, text/html;q=0.6'])->prefers(['html', 'json']));

        $this->assertEquals('application/json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8'])->prefers('application/json'));
        $this->assertEquals('application/json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json, text/html'])->prefers(['text/html', 'application/json']));
        $this->assertEquals('text/html', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json;q=0.4, text/html;q=0.6'])->prefers(['text/html', 'application/json']));
        $this->assertEquals('text/html', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json;q=0.4, text/html;q=0.6'])->prefers(['application/json', 'text/html']));

        $this->assertEquals('json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*; charset=utf-8'])->prefers('json'));
        $this->assertEquals('application/json', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*'])->prefers('application/json'));
        $this->assertEquals('application/xml', Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*'])->prefers('application/xml'));
        $this->assertNull(Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*'])->prefers('text/html'));
    }

    public function testAllInputReturnsInputAndFiles()
    {
        $file = $this->getMockBuilder('Illuminate\Http\UploadedFile')->setConstructorArgs([__FILE__, 'photo.jpg'])->getMock();
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => 'bar'], [], ['baz' => $file]);
        $this->assertEquals(['foo' => 'bar', 'baz' => $file, 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputReturnsNestedInputAndFiles()
    {
        $file = $this->getMockBuilder('Illuminate\Http\UploadedFile')->setConstructorArgs([__FILE__, 'photo.jpg'])->getMock();
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], ['foo' => ['photo' => $file]]);
        $this->assertEquals(['foo' => ['bar' => 'baz', 'photo' => $file], 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputReturnsInputAfterReplace()
    {
        $request = Request::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']]);
        $request->replace(['foo' => ['bar' => 'baz'], 'boom' => 'breeze']);
        $this->assertEquals(['foo' => ['bar' => 'baz'], 'boom' => 'breeze'], $request->all());
    }

    public function testAllInputWithNumericKeysReturnsInputAfterReplace()
    {
        $request1 = Request::create('/', 'POST', [0 => 'A', 1 => 'B', 2 => 'C']);
        $request1->replace([0 => 'A', 1 => 'B', 2 => 'C']);
        $this->assertEquals([0 => 'A', 1 => 'B', 2 => 'C'], $request1->all());

        $request2 = Request::create('/', 'POST', [1 => 'A', 2 => 'B', 3 => 'C']);
        $request2->replace([1 => 'A', 2 => 'B', 3 => 'C']);
        $this->assertEquals([1 => 'A', 2 => 'B', 3 => 'C'], $request2->all());
    }

    public function testInputWithEmptyFilename()
    {
        $invalidFiles = [
            'file' => [
                'name' => null,
                'type' => null,
                'tmp_name' => null,
                'error' => 4,
                'size' => 0,
            ],
        ];

        $baseRequest = SymfonyRequest::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], $invalidFiles);

        $request = Request::createFromBase($baseRequest);
    }

    public function testMultipleFileUploadWithEmptyValue()
    {
        $invalidFiles = [
            'file' => [
                'name' => [''],
                'type' => [''],
                'tmp_name' => [''],
                'error' => [4],
                'size' => [0],
            ],
        ];

        $baseRequest = SymfonyRequest::create('/?boom=breeze', 'GET', ['foo' => ['bar' => 'baz']], [], $invalidFiles);

        $request = Request::createFromBase($baseRequest);

        $this->assertEmpty($request->files->all());
    }

    public function testOldMethodCallsSession()
    {
        $request = Request::create('/', 'GET');
        $session = m::mock('Illuminate\Session\Store');
        $session->shouldReceive('getOldInput')->once()->with('foo', 'bar')->andReturn('boom');
        $request->setLaravelSession($session);
        $this->assertEquals('boom', $request->old('foo', 'bar'));
    }

    public function testFlushMethodCallsSession()
    {
        $request = Request::create('/', 'GET');
        $session = m::mock('Illuminate\Session\Store');
        $session->shouldReceive('flashInput')->once();
        $request->setLaravelSession($session);
        $request->flush();
    }

    public function testFormatReturnsAcceptableFormat()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertEquals('json', $request->format());
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8']);
        $this->assertEquals('json', $request->format());
        $this->assertTrue($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/atom+xml']);
        $this->assertEquals('atom', $request->format());
        $this->assertFalse($request->wantsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'is/not/known']);
        $this->assertEquals('html', $request->format());
        $this->assertEquals('foo', $request->format('foo'));
    }

    public function testFormatReturnsAcceptsJson()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertEquals('json', $request->format());
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
        $this->assertTrue($request->acceptsJson());
        $this->assertFalse($request->acceptsHtml());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/foo+json']);
        $this->assertTrue($request->accepts('application/foo+json'));
        $this->assertFalse($request->accepts('application/bar+json'));
        $this->assertFalse($request->accepts('application/json'));

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/*']);
        $this->assertTrue($request->accepts('application/xml'));
        $this->assertTrue($request->accepts('application/json'));
    }

    public function testFormatReturnsAcceptsHtml()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->assertEquals('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertFalse($request->acceptsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/*']);
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('text/plain'));
    }

    public function testFormatReturnsAcceptsAll()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*/*']);
        $this->assertEquals('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('foo/bar'));
        $this->assertTrue($request->accepts('application/baz+xml'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertTrue($request->acceptsJson());

        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '*']);
        $this->assertEquals('html', $request->format());
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('foo/bar'));
        $this->assertTrue($request->accepts('application/baz+xml'));
        $this->assertTrue($request->acceptsHtml());
        $this->assertTrue($request->acceptsJson());
    }

    public function testFormatReturnsAcceptsMultiple()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json,text/*']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('text/foo'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
    }

    public function testFormatReturnsAcceptsCharset()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json; charset=utf-8']);
        $this->assertTrue($request->accepts(['text/html', 'application/json']));
        $this->assertFalse($request->accepts('text/html'));
        $this->assertFalse($request->accepts('text/foo'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->accepts('application/baz+json'));
    }

    public function testBadAcceptHeader()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pt-PT; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 (.NET CLR 3.5.30729)']);
        $this->assertFalse($request->accepts(['text/html', 'application/json']));
        $this->assertFalse($request->accepts('text/html'));
        $this->assertFalse($request->accepts('text/foo'));
        $this->assertFalse($request->accepts('application/json'));
        $this->assertFalse($request->accepts('application/baz+json'));
        $this->assertFalse($request->acceptsHtml());
        $this->assertFalse($request->acceptsJson());

        // Should not be handled as regex.
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '.+/.+']);
        $this->assertFalse($request->accepts('application/json'));
        $this->assertFalse($request->accepts('application/baz+json'));

        // Should not produce compilation error on invalid regex.
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '(/(']);
        $this->assertFalse($request->accepts('text/html'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSessionMethod()
    {
        $request = Request::create('/', 'GET');
        $request->session();
    }

    public function testUserResolverMakesUserAvailableAsMagicProperty()
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $request->setUserResolver(function () {
            return 'user';
        });
        $this->assertEquals('user', $request->user());
    }

    public function testFingerprintMethod()
    {
        $request = Request::create('/', 'GET', [], [], [], []);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar/{id}', []);
            $route->bind($request);

            return $route;
        });

        $this->assertEquals(40, mb_strlen($request->fingerprint()));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testFingerprintWithoutRoute()
    {
        $request = Request::create('/', 'GET', [], [], [], []);
        $request->fingerprint();
    }

    public function testCreateFromBase()
    {
        $body = [
            'foo' => 'bar',
            'baz' => ['qux'],
        ];

        $server = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $base = SymfonyRequest::create('/', 'GET', [], [], [], $server, json_encode($body));

        $request = Request::createFromBase($base);

        $this->assertEquals($request->request->all(), $body);
    }

    /**
     * Tests for Http\Request magic methods `__get()` and `__isset()`.
     *
     * @link https://github.com/laravel/framework/issues/10403 Form request object attribute returns empty when have some string.
     */
    public function testMagicMethods()
    {
        // Simulates QueryStrings.
        $request = Request::create('/', 'GET', ['foo' => 'bar', 'empty' => '']);

        // Parameter 'foo' is 'bar', then it ISSET and is NOT EMPTY.
        $this->assertEquals($request->foo, 'bar');
        $this->assertEquals(isset($request->foo), true);
        $this->assertEquals(empty($request->foo), false);

        // Parameter 'empty' is '', then it ISSET and is EMPTY.
        $this->assertEquals($request->empty, '');
        $this->assertEquals(isset($request->empty), true);
        $this->assertEquals(empty($request->empty), true);

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertEquals($request->undefined, null);
        $this->assertEquals(isset($request->undefined), false);
        $this->assertEquals(empty($request->undefined), true);

        // Simulates Route parameters.
        $request = Request::create('/example/bar', 'GET', ['xyz' => 'overwritten']);
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/example/{foo}/{xyz?}/{undefined?}', []);
            $route->bind($request);

            return $route;
        });

        // Router parameter 'foo' is 'bar', then it ISSET and is NOT EMPTY.
        $this->assertEquals($request->foo, 'bar');
        $this->assertEquals(isset($request->foo), true);
        $this->assertEquals(empty($request->foo), false);

        // Router parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertEquals($request->undefined, null);
        $this->assertEquals(isset($request->undefined), false);
        $this->assertEquals(empty($request->undefined), true);

        // Special case: router parameter 'xyz' is 'overwritten' by QueryString, then it ISSET and is NOT EMPTY.
        // Basically, QueryStrings have priority over router parameters.
        $this->assertEquals($request->xyz, 'overwritten');
        $this->assertEquals(isset($request->foo), true);
        $this->assertEquals(empty($request->foo), false);

        // Simulates empty QueryString and Routes.
        $request = Request::create('/', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/', []);
            $route->bind($request);

            return $route;
        });

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertEquals($request->undefined, null);
        $this->assertEquals(isset($request->undefined), false);
        $this->assertEquals(empty($request->undefined), true);

        // Special case: simulates empty QueryString and Routes, without the Route Resolver.
        // It'll happen when you try to get a parameter outside a route.
        $request = Request::create('/', 'GET');

        // Parameter 'undefined' is undefined/null, then it NOT ISSET and is EMPTY.
        $this->assertEquals($request->undefined, null);
        $this->assertEquals(isset($request->undefined), false);
        $this->assertEquals(empty($request->undefined), true);
    }

    public function testHttpRequestFlashCallsSessionFlashInputWithInputData()
    {
        $session = m::mock('Illuminate\Session\Store');
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor', 'email' => 'foo']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flash();
    }

    public function testHttpRequestFlashOnlyCallsFlashWithProperParameters()
    {
        $session = m::mock('Illuminate\Session\Store');
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flashOnly(['name']);
    }

    public function testHttpRequestFlashExceptCallsFlashWithProperParameters()
    {
        $session = m::mock('Illuminate\Session\Store');
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $request = Request::create('/', 'GET', ['name' => 'Taylor', 'email' => 'foo']);
        $request->setLaravelSession($session);
        $request->flashExcept(['email']);
    }
}
