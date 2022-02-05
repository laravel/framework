<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TrimStringsTest extends TestCase
{
    public function testTrimStringsIgnoringExceptAttribute()
    {
        $middleware = new TrimStringsWithExceptAttribute;
        $symfonyRequest = new SymfonyRequest([
            'abc' => '  123  ',
            'xyz' => '  456  ',
            'foo' => '  789  ',
            'bar' => '  010  ',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('123', $request->get('abc'));
            $this->assertSame('456', $request->get('xyz'));
            $this->assertSame('  789  ', $request->get('foo'));
            $this->assertSame('  010  ', $request->get('bar'));
        });
    }

    public function testTrimStringsNBSP()
    {
        $middleware = new TrimStrings;
        $symfonyRequest = new SymfonyRequest([
            // Here has some NBSP, but it still display to space.
            'abc' => '   123    ',
            'xyz' => 'だ',
            'foo' => 'ム',
            'bar' => '   だ    ',
            'baz' => '   ム    ',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('123', $request->get('abc'));
            $this->assertSame('だ', $request->get('xyz'));
            $this->assertSame('ム', $request->get('foo'));
            $this->assertSame('だ', $request->get('bar'));
            $this->assertSame('ム', $request->get('baz'));
        });
    }
}

class TrimStringsWithExceptAttribute extends TrimStrings
{
    protected $except = [
        'foo',
        'bar',
    ];
}
