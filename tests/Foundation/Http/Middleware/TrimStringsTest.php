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
            /**/
            'temp' => [
                'abc' => '  123  ',
                'xyz' => '  456  ',
                'foo' => '  789  ',
                'bar' => '  010  ',
            ],
            /**/
            'temps' => [
                [
                    'abc' => '  123  ',
                    'xyz' => '  456  ',
                    'foo' => '  789  ',
                    'bar' => '  010  ',
                ],
            ],
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('123', $request->get('abc'));
            $this->assertSame('456', $request->get('xyz'));
            $this->assertSame('  789  ', $request->get('foo'));
            $this->assertSame('  010  ', $request->get('bar'));
            /**/
            $this->assertSame('123', $request->input('temp.abc'));
            $this->assertSame('456', $request->input('temp.xyz'));
            $this->assertSame('  789  ', $request->input('temp.foo'));
            $this->assertSame('  010  ', $request->input('temp.bar'));
            /**/
            $this->assertSame('123', $request->input('temps.0.abc'));
            $this->assertSame('456', $request->input('temps.0.xyz'));
            $this->assertSame('  789  ', $request->input('temps.0.foo'));
            $this->assertSame('  010  ', $request->input('temps.0.bar'));
        });
    }

    public function testTrimStringsNBSP()
    {
        $middleware = new TrimStrings;
        $symfonyRequest = new SymfonyRequest([
            // Here has some NBSP, but it still display to space.
            // Please note, do not edit in browser
            'abc' => '   123    ',
            'zwnbsp' => '﻿  ha  ﻿﻿',
            'xyz' => 'だ',
            'foo' => 'ム',
            'bar' => '   だ    ',
            'baz' => '   ム    ',
            'binary' => " \xE9  ",
            /**/
            'temp' => [
                'abc' => '   123    ',
                'zwnbsp' => '﻿  ha  ﻿﻿',
                'xyz' => 'だ',
                'foo' => 'ム',
                'bar' => '   だ    ',
                'baz' => '   ム    ',
                'binary' => " \xE9  ",
            ],
            /**/
            'temps' => [
                [
                    'abc' => '   123    ',
                    'zwnbsp' => '﻿  ha  ﻿﻿',
                    'xyz' => 'だ',
                    'foo' => 'ム',
                    'bar' => '   だ    ',
                    'baz' => '   ム    ',
                    'binary' => " \xE9  ",
                ],
            ],
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('123', $request->get('abc'));
            $this->assertSame('ha', $request->get('zwnbsp'));
            $this->assertSame('だ', $request->get('xyz'));
            $this->assertSame('ム', $request->get('foo'));
            $this->assertSame('だ', $request->get('bar'));
            $this->assertSame('ム', $request->get('baz'));
            $this->assertSame("\xE9", $request->get('binary'));
            /**/
            $this->assertSame('123', $request->input('temp.abc'));
            $this->assertSame('ha', $request->input('temp.zwnbsp'));
            $this->assertSame('だ', $request->input('temp.xyz'));
            $this->assertSame('ム', $request->input('temp.foo'));
            $this->assertSame('だ', $request->input('temp.bar'));
            $this->assertSame('ム', $request->input('temp.baz'));
            $this->assertSame("\xE9", $request->input('temp.binary'));
            /**/
            $this->assertSame('123', $request->input('temps.0.abc'));
            $this->assertSame('ha', $request->input('temps.0.zwnbsp'));
            $this->assertSame('だ', $request->input('temps.0.xyz'));
            $this->assertSame('ム', $request->input('temps.0.foo'));
            $this->assertSame('だ', $request->input('temps.0.bar'));
            $this->assertSame('ム', $request->input('temps.0.baz'));
            $this->assertSame("\xE9", $request->input('temps.0.binary'));
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
