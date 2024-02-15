<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        ConvertEmptyStringsToNull::flushState();
        TrimStrings::flushState();
    }

    public function testConvertEmptyStringsToNull()
    {
        $configuration = new Middleware();
        $middleware = new ConvertEmptyStringsToNull();

        $configuration->convertEmptyStringsToNull(except: [
            fn (Request $request) => $request->has('skip-all-1'),
            fn (Request $request) => $request->has('skip-all-2'),
        ]);

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
        ]);

        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertNull($request->get('bbb'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
            'skip-all-1' => 'true',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('', $request->get('bbb'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '',
            'skip-all-2' => 'true',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('', $request->get('bbb'));
    }

    public function testTrimStrings()
    {
        $configuration = new Middleware();
        $middleware = new TrimStrings();

        $configuration->trimStrings(except: [
            'aaa',
            fn (Request $request) => $request->has('skip-all'),
        ]);

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('456', $request->get('bbb'));
        $this->assertSame('789', $request->get('ccc'));

        $symfonyRequest = new SymfonyRequest([
            'aaa' => '  123  ',
            'bbb' => '  456  ',
            'ccc' => '  789  ',
            'skip-all' => true,
        ]);
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $request = Request::createFromBase($symfonyRequest);

        $request = $middleware->handle($request, fn (Request $request) => $request);

        $this->assertSame('  123  ', $request->get('aaa'));
        $this->assertSame('  456  ', $request->get('bbb'));
        $this->assertSame('  789  ', $request->get('ccc'));
    }
}
