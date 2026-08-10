<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\Store;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;

class RoutingRedirectorTest extends TestCase
{
    protected $headers;
    protected $request;
    protected $url;
    protected $session;
    protected $redirect;

    protected function setUp(): void
    {
        $this->headers = Mockery::mock(HeaderBag::class);

        $this->request = Mockery::mock(Request::class);
        $this->request->shouldReceive('isMethod')->andReturn(true)->byDefault();
        $this->request->shouldReceive('method')->andReturn('GET')->byDefault();
        $this->request->shouldReceive('route')->andReturn(true)->byDefault();
        $this->request->shouldReceive('ajax')->andReturn(false)->byDefault();
        $this->request->shouldReceive('expectsJson')->andReturn(false)->byDefault();
        $this->request->headers = $this->headers;

        $this->url = Mockery::mock(UrlGenerator::class);
        $this->url->shouldReceive('getRequest')->andReturn($this->request);
        $this->url->shouldReceive('to')->with('bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('bar', [], true)->andReturn('https://foo.com/bar');
        $this->url->shouldReceive('to')->with('login', [], null)->andReturn('http://foo.com/login');
        $this->url->shouldReceive('to')->with('http://foo.com/bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('/', [], null)->andReturn('http://foo.com/');
        $this->url->shouldReceive('to')->with('http://foo.com/bar?signature=secret', [], null)->andReturn('http://foo.com/bar?signature=secret');

        $this->session = Mockery::mock(Store::class);

        $this->redirect = new Redirector($this->url);
        $this->redirect->setSession($this->session);
    }

    public function testBasicRedirectTo()
    {
        $response = $this->redirect->to('bar');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($this->session, $response->getSession());
    }

    public function testComplexRedirectTo()
    {
        $response = $this->redirect->to('bar', 303, ['X-RateLimit-Limit' => 60, 'X-RateLimit-Remaining' => 59], true);

        $this->assertSame('https://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(59, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function testGuestPutCurrentUrlInSession()
    {
        $this->url->expects('full')->andReturn('http://foo.com/bar');
        $this->session->expects('put')->with('url.intended', 'http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
    }

    public function testGuestPutPreviousUrlInSession()
    {
        $this->request->expects('isMethod')->with('GET')->andReturn(false);
        $this->session->expects('put')->with('url.intended', 'http://foo.com/bar');
        $this->url->expects('previous')->andReturn('http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
    }

    public function testIntendedRedirectToIntendedUrlInSession()
    {
        $this->session->expects('pull')->with('url.intended', '/')->andReturn('http://foo.com/bar');

        $response = $this->redirect->intended();

        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testIntendedWithoutIntendedUrlInSession()
    {
        // without fallback url
        $this->session->expects('pull')->with('url.intended', '/')->andReturn('/');
        $response = $this->redirect->intended();
        $this->assertSame('http://foo.com/', $response->getTargetUrl());

        // with a fallback url
        $this->session->expects('pull')->with('url.intended', 'bar')->andReturn('bar');
        $response = $this->redirect->intended('bar');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRefreshRedirectToCurrentUrl()
    {
        $this->request->expects('path')->andReturn('http://foo.com/bar');
        $response = $this->redirect->refresh();
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testBackRedirectToHttpReferer()
    {
        $this->url->expects('previous')->andReturn('http://foo.com/bar');
        $response = $this->redirect->back();
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testAwayDoesntValidateTheUrl()
    {
        $response = $this->redirect->away('bar');
        $this->assertSame('bar', $response->getTargetUrl());
    }

    public function testSecureRedirectToHttpsUrl()
    {
        $response = $this->redirect->secure('bar');
        $this->assertSame('https://foo.com/bar', $response->getTargetUrl());
    }

    public function testAction()
    {
        $this->url->expects('action')->with('bar@index', [])->andReturn('http://foo.com/bar');
        $response = $this->redirect->action('bar@index');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRoute()
    {
        $this->url->expects('route')->with('home', [])->andReturn('http://foo.com/bar');

        $response = $this->redirect->route('home');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testSignedRoute()
    {
        $this->url->expects('signedRoute')->with('home', [], null)->andReturn('http://foo.com/bar?signature=secret');

        $response = $this->redirect->signedRoute('home');
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function testTemporarySignedRoute()
    {
        $this->url->expects('temporarySignedRoute')->with('home', 10, [])->andReturn('http://foo.com/bar?signature=secret');

        $response = $this->redirect->temporarySignedRoute('home', 10);
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function testItSetsAndGetsValidIntendedUrl()
    {
        $this->session->expects('put')->with('url.intended', 'http://foo.com/bar');
        $this->session->expects('get')->andReturn('http://foo.com/bar');

        $result = $this->redirect->setIntendedUrl('http://foo.com/bar');
        $this->assertInstanceOf(Redirector::class, $result);

        $this->assertSame('http://foo.com/bar', $this->redirect->getIntendedUrl());
    }
}
