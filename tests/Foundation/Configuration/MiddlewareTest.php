<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        TrustProxies::flushState();
        TrimStrings::flushState();
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

    public function testTrustProxies()
    {
        $configuration = new Middleware();
        $middleware = new TrustProxies;

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('proxies');
        $method->setAccessible(true);

        $property = $reflection->getProperty('proxies');
        $property->setAccessible(true);

        $this->assertNull($method->invoke($middleware));

        $property->setValue($middleware, [
            '192.168.1.1',
            '192.168.1.2',
        ]);

        $this->assertEquals([
            '192.168.1.1',
            '192.168.1.2',
        ], $method->invoke($middleware));

        $configuration->trustProxies(at: '*');
        $this->assertEquals('*', $method->invoke($middleware));

        $configuration->trustProxies(at: [
            '192.168.1.3',
            '192.168.1.4',
        ]);
        $this->assertEquals([
            '192.168.1.3',
            '192.168.1.4',
        ], $method->invoke($middleware));
    }
}
