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
        $middleware = new TrimStringsWithExceptAttribute();
        $symfonyRequest = new SymfonyRequest([
            'abc' => '  123  ',
            'xyz' => '  456  ',
            'foo' => '  789  ',
            'bar' => '  010  ',
            'pqr' => [' 000 ', '111', ' 123'],
            'lmn' => [
                [
                    'abc' => ' 123 ',
                    'pqr' => ' 111 ',
                ],
                [
                    'abc' => ' 222 ',
                    'pqr' => ' 333 ',
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
            $this->assertSame(' 000 ', $request->get('pqr')[0]);
            $this->assertSame('111', $request->get('pqr')[1]);
            $this->assertSame(' 123', $request->get('pqr')[2]);
            $this->assertSame(' 123 ', $request->get('lmn')[0]['abc']);
            $this->assertSame('111', $request->get('lmn')[0]['pqr']);
            $this->assertSame(' 222 ', $request->get('lmn')[1]['abc']);
            $this->assertSame('333', $request->get('lmn')[1]['pqr']);
        });
    }
}

class TrimStringsWithExceptAttribute extends TrimStrings
{
    protected $except = [
        'foo',
        'bar',
        'pqr.*',
        'lmn.*.abc',
    ];
}
