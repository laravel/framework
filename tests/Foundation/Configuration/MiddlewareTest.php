<?php

namespace Illuminate\Tests\Foundation\Configuration;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class MiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        Container::setInstance(null);
        ConvertEmptyStringsToNull::flushState();
        EncryptCookies::flushState();
        PreventRequestsDuringMaintenance::flushState();
        TrimStrings::flushState();
        TrustProxies::flushState();
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

    public function testTrustProxies()
    {
        $configuration = new Middleware();
        $middleware = new TrustProxies;

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('proxies');
        $property = $reflection->getProperty('proxies');

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

    public function testTrustHeaders()
    {
        $configuration = new Middleware();
        $middleware = new TrustProxies;

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('headers');
        $property = $reflection->getProperty('headers');

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_PREFIX |
            Request::HEADER_X_FORWARDED_AWS_ELB, $method->invoke($middleware));

        $property->setValue($middleware, Request::HEADER_X_FORWARDED_AWS_ELB);

        $this->assertEquals(Request::HEADER_X_FORWARDED_AWS_ELB, $method->invoke($middleware));

        $configuration->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR);

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR, $method->invoke($middleware));

        $configuration->trustProxies([
            '192.168.1.3',
            '192.168.1.4',
        ], Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT
        );

        $this->assertEquals(Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT, $method->invoke($middleware));
    }

    public function testTrustHosts()
    {
        $app = Mockery::mock(Application::class);
        $configuration = new Middleware();
        $middleware = new class($app) extends TrustHosts
        {
            protected function allSubdomainsOfApplicationUrl()
            {
                return '^(.+\.)?laravel\.test$';
            }
        };

        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts();
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: static fn () => ['my.test']);
        $this->assertEquals(['my.test', '^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: ['my.test'], subdomains: false);
        $this->assertEquals(['my.test'], $middleware->hosts());

        $configuration->trustHosts(at: static fn () => ['my.test'], subdomains: false);
        $this->assertEquals(['my.test'], $middleware->hosts());

        $configuration->trustHosts(at: []);
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: static fn () => []);
        $this->assertEquals(['^(.+\.)?laravel\.test$'], $middleware->hosts());

        $configuration->trustHosts(at: [], subdomains: false);
        $this->assertEquals([], $middleware->hosts());

        $configuration->trustHosts(at: static fn () => [], subdomains: false);
        $this->assertEquals([], $middleware->hosts());
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

    public function testPreventRequestsDuringMaintenance()
    {
        $configuration = new Middleware();

        $mode = Mockery::mock(MaintenanceMode::class);
        $mode->shouldReceive('active')->andReturn(true);
        $mode->shouldReceive('date')->andReturn([]);
        $app = Mockery::mock(Application::class);
        $app->shouldReceive('maintenanceMode')->andReturn($mode);
        $middleware = new PreventRequestsDuringMaintenance($app);

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('inExceptArray');

        $symfonyRequest = new SymfonyRequest();
        $symfonyRequest->server->set('REQUEST_METHOD', 'GET');
        $symfonyRequest->server->set('REQUEST_URI', 'metrics/requests');

        $request = Request::createFromBase($symfonyRequest);
        $this->assertFalse($method->invoke($middleware, $request));

        $configuration->preventRequestsDuringMaintenance(['metrics/*']);
        $this->assertTrue($method->invoke($middleware, $request));
    }
}
