<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfTokenAdvanced;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\StubSessionHandler;

class VerifyCsrfTokenAdvancedTest extends TestCase
{
    protected $app;
    protected $encrypter;
    protected $middleware;
    protected $request;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Container;
        $this->encrypter = $this->createMock(Encrypter::class);
        
        $this->app->instance('config', new Repository([
            'session' => [
                'lifetime' => 120,
                'path' => '/',
                'domain' => null,
            ],
            'security' => [
                'csrf' => [
                    'double_submit_cookie' => true,
                    'expiration' => 60,
                ],
            ],
        ]));

        $this->middleware = new VerifyCsrfTokenAdvanced($this->app, $this->encrypter);

        $this->request = new Request();
        
        $this->session = new Store('test-session', new ArraySessionHandler(1));
        $this->session->put('_token', 'test-token');
        
        $this->request->setLaravelSession($this->session);
    }

    public function testTokensMatch()
    {
        $this->request->headers->set('X-CSRF-TOKEN', 'test-token');
        
        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    public function testTokensWithExpirationMatch()
    {
        // Token with future expiration
        $tokenWithExpiration = 'test-token|' . (time() + 3600);
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);
        
        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }

    public function testTokensWithExpiredTimeDoNotMatch()
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        
        // Token with past expiration
        $tokenWithExpiration = 'test-token|' . (time() - 3600);
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);
        
        $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
    }

    public function testInvalidFormatTokensDoNotMatch()
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        
        // Token with invalid format
        $tokenWithExpiration = 'test-token|invalid-timestamp';
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);
        
        $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
    }

    public function testCanSkipMiddlewareForExceptUrls()
    {
        $this->middleware->except = ['test/*'];
        
        $this->request->server->set('REQUEST_URI', 'test/route');
        
        $response = $this->middleware->handle($this->request, function () {
            return new Response('SKIPPED');
        });
        
        $this->assertEquals('SKIPPED', $response->getContent());
    }

    public function testAddsCookiesToResponse()
    {
        $this->request->headers->set('X-CSRF-TOKEN', 'test-token');
        
        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
        
        $cookies = $response->headers->getCookies();
        
        // Should add two cookies - XSRF-TOKEN and csrf_token
        $this->assertCount(2, $cookies);
        
        $cookieNames = array_map(function ($cookie) {
            return $cookie->getName();
        }, $cookies);
        
        $this->assertContains('XSRF-TOKEN', $cookieNames);
        $this->assertContains('csrf_token', $cookieNames);
    }
} 