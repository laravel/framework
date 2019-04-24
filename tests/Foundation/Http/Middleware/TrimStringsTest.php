<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as TransformsRequest;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TrimStringsTest extends TestCase
{
    public function testTrimmingWhenDataIsPlain()
    {
        $middleware = new TrimStrings;

        $symfonyRequest = new SymfonyRequest([
            'foo' => 'foz  ',
            'bar' => 'baz',
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('foz', $request->get('foo'));
            $this->assertEquals('baz', $request->get('bar'));
        });
    }

    public function testTrimmingExactExceptionsWhenDataIsPlain()
    {
        $middleware = new TrimStrings;

        $symfonyRequest = new SymfonyRequest([
            'foo' => 'foz  ',
            'for' => 'baz  ',
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('foz', $request->get('foo'));
            $this->assertEquals('baz  ', $request->get('for'));
        });
    }

    public function testTrimmingPatternExceptionsWhenDataIsPlain()
    {
        $middleware = new TrimStrings;

        $symfonyRequest = new SymfonyRequest([
            'foo_pat' => 'foz  ',
            'foo_pattern' => 'far  ',
            'foo_123' => 'bar  '
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals('foz  ', $request->get('foo_pat'));
            $this->assertEquals('far  ', $request->get('foo_pattern'));
            $this->assertEquals('bar', $request->get('foo_123'));
        });
    }

    public function testTrimmingWhenDataIsNested()
    {
        $middleware = new TrimStrings;

        $symfonyRequest = new SymfonyRequest([
            'foo' => [['for' => 'foz  ']],
            'for' => [['foo' => 'foz  ']],
            'bar' => ['baz'],
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals([['for' => 'foz']], $request->get('foo'));
            $this->assertEquals([['foo' => 'foz']], $request->get('for'));
            $this->assertEquals(['baz'], $request->get('bar'));
        });
    }

    public function testTrimmingPatternExceptionsWhenDataIsNested()
    {
        $middleware = new TrimStrings;

        $symfonyRequest = new SymfonyRequest([
            'for' => [['for' => 'foz  ']],
            'bar' => ['baz  '],
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'POST');
        $request = Request::createFromBase($symfonyRequest);

        $middleware->handle($request, function (Request $request) {
            $this->assertEquals([['for' => 'foz  ']], $request->get('for'));
            $this->assertEquals(['baz  '], $request->get('bar'));
        });
    }
}

class TrimStrings extends TransformsRequest
{
    /**
     * The exact attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        'for'
    ];

    /**
     * The regular expressions matching attributes that should not be trimmed.
     *
     * @var array
     */
    protected $exceptPattern = [
        '^foo_[a-z]+$',
        '^for\.[0-9]\.for$',
        '^bar\.[0-9]$',
    ];
}
