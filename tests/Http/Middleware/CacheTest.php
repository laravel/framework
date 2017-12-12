<?php

namespace Illuminate\Tests\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\Middleware\Cache;

class CacheTest extends TestCase
{
    public function testDoNotSetHeaderWhenMethodNotCacheable()
    {
        $request = new Request();
        $request->setMethod('PUT');

        $response = (new Cache())->handle($request, function () {
            return new Response('Hello Laravel');
        }, 'max_age=120;s_maxage=60');

        $this->assertNull($response->getMaxAge());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response();
        }, 'max_age=120;s_maxage=60');

        $this->assertNull($response->getMaxAge());
        $this->assertNull($response->getEtag());
    }

    public function testAddHeaders()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response('some content');
        }, 'max_age=100;s_maxage=200;etag=ABC');

        $this->assertSame('"ABC"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testAddHeadersUsingArray()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response('some content');
        }, ['max_age' => 100, 's_maxage' => 200, 'etag' => 'ABC']);

        $this->assertSame('"ABC"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testGenerateEtag()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response('some content');
        }, 'etag;max_age=100;s_maxage=200');

        $this->assertSame('"9893532233caff98cd083a116b013c0b"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }

    public function testIsNotModified()
    {
        $request = new Request();
        $request->headers->set('If-None-Match', '"9893532233caff98cd083a116b013c0b"');

        $response = (new Cache())->handle($request, function () {
            return new Response('some content');
        }, 'etag;max_age=100;s_maxage=200');

        $this->assertSame(304, $response->getStatusCode());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidOption()
    {
        (new Cache())->handle(new Request(), function () {
            return new Response('some content');
        }, 'invalid');
    }
}
