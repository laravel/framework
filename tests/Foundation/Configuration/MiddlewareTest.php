<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        EncryptCookies::flushState();
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

    public function testEncryptCookies()
    {
        $configuration = new Middleware();
        $encrypter = Mockery::mock(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);

        $this->assertFalse($middleware->isDisabled('aaa'));
        $this->assertFalse($middleware->isDisabled('bbb'));

        $configuration->encryptCookies(except: [
            'aaa',
            'bbb',
        ]);

        $this->assertTrue($middleware->isDisabled('aaa'));
        $this->assertTrue($middleware->isDisabled('bbb'));
    }
}
