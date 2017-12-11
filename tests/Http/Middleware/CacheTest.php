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
        }, 1, 2, true, true);

        $this->assertNull($response->getMaxAge());
        $this->assertNull($response->getEtag());
    }

    public function testDoNotSetHeaderWhenNoContent()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response();
        }, 1, 2, true, true);

        $this->assertNull($response->getMaxAge());
        $this->assertNull($response->getEtag());
    }

    public function testAddHeaders()
    {
        $response = (new Cache())->handle(new Request(), function () {
            return new Response('some content');
        }, 100, 200, true, true);

        $this->assertSame('"9893532233caff98cd083a116b013c0b"', $response->getEtag());
        $this->assertSame('max-age=100, public, s-maxage=200', $response->headers->get('Cache-Control'));
    }
}
