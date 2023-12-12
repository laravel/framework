<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ConvertEmptyStringsToNullTest extends TestCase
{
    public function testConvertsEmptyStringsToNull()
    {
        $middleware = new ConvertEmptyStringsToNull;
        $symfonyRequest = new SymfonyRequest([
            'foo' => 'bar',
            'baz' => '',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('bar', $request->get('foo'));
            $this->assertNull($request->get('baz'));
        });
    }

    public function testSkipConvertsEmptyStringsToNull()
    {
        $middleware = new ConvertEmptyStringsToNull;
        ConvertEmptyStringsToNull::skipWhen(fn ($request) => '' === $request->baz);
        $symfonyRequest = new SymfonyRequest([
            'foo' => 'bar',
            'baz' => '',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertSame('bar', $request->get('foo'));
            $this->assertSame('', $request->get('baz'));
        });
    }
}
