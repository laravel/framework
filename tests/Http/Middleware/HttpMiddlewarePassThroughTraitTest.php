<?php

use Illuminate\Http\Request;

class HttpMiddlewarePassThroughTraitTest extends PHPUnit_Framework_TestCase
{
    use Illuminate\Http\Middleware\PassThroughTrait;

    protected $except = [];

    public function testShouldPassThrough()
    {
        $this->except = ['stripe/*'];

        $request = Request::create('stripe/bar', 'GET');
        $this->assertTrue($this->shouldPassThrough($request));

        $request = Request::create('foo/stripe/bar', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('/foo/bar', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('stripe', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $this->except = ['stripe/*', '*user', '*foo/bar*'];

        $request = Request::create('stripe', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('user', 'GET');
        $this->assertTrue($this->shouldPassThrough($request));

        $request = Request::create('admin/user', 'GET');
        $this->assertTrue($this->shouldPassThrough($request));

        $request = Request::create('admin/foo/bar', 'GET');
        $this->assertTrue($this->shouldPassThrough($request));

        $request = Request::create('foo/bar/baz', 'GET');
        $this->assertTrue($this->shouldPassThrough($request));

        $request = Request::create('user/profile', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('foo', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('bar', 'GET');
        $this->assertFalse($this->shouldPassThrough($request));
    }

    public function testShouldPassThroughWithEmptyExcept()
    {
        $this->except = [];

        $request = Request::create('', 'GET');

        $this->assertFalse($this->shouldPassThrough($request));

        $request = Request::create('/foo/bar', 'GET');

        $this->assertFalse($this->shouldPassThrough($request));
    }
}