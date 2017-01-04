<?php

namespace Illuminate\Tests\Routing;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Routing\Redirector;

class RoutingRedirectorTest extends TestCase
{
    protected $headers;
    protected $request;
    protected $url;
    protected $session;
    protected $redirect;

    public function setUp()
    {
        $this->headers = m::mock('Symfony\Component\HttpFoundation\HeaderBag');

        $this->request = m::mock('Illuminate\Http\Request');
        $this->request->headers = $this->headers;

        $this->url = m::mock('Illuminate\Routing\UrlGenerator');
        $this->url->shouldReceive('getRequest')->andReturn($this->request);
        $this->url->shouldReceive('to')->with('bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('bar', [], true)->andReturn('https://foo.com/bar');
        $this->url->shouldReceive('to')->with('login', [], null)->andReturn('http://foo.com/login');
        $this->url->shouldReceive('to')->with('http://foo.com/bar', [], null)->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('to')->with('/', [], null)->andReturn('http://foo.com/');

        $this->session = m::mock('Illuminate\Session\Store');

        $this->redirect = new Redirector($this->url);
        $this->redirect->setSession($this->session);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testBasicRedirectTo()
    {
        $response = $this->redirect->to('bar');

        $this->assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($this->session, $response->getSession());
    }

    public function testComplexRedirectTo()
    {
        $response = $this->redirect->to('bar', 303, ['X-RateLimit-Limit' => 60, 'X-RateLimit-Remaining' => 59], true);

        $this->assertEquals('https://foo.com/bar', $response->getTargetUrl());
        $this->assertEquals(303, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(59, $response->headers->get('X-RateLimit-Remaining'));
    }

    public function testGuestPutCurrentUrlInSession()
    {
        $this->url->shouldReceive('full')->andReturn('http://foo.com/bar');
        $this->session->shouldReceive('put')->once()->with('url.intended', 'http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertEquals('http://foo.com/login', $response->getTargetUrl());
    }

    public function testIntendedRedirectToIntendedUrlInSession()
    {
        $this->session->shouldReceive('pull')->with('url.intended', '/')->andReturn('http://foo.com/bar');

        $response = $this->redirect->intended();

        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testIntendedWithoutIntendedUrlInSession()
    {
        $this->session->shouldReceive('forget')->with('url.intended');

        // without fallback url
        $this->session->shouldReceive('pull')->with('url.intended', '/')->andReturn('/');
        $response = $this->redirect->intended();
        $this->assertEquals('http://foo.com/', $response->getTargetUrl());

        // with a fallback url
        $this->session->shouldReceive('pull')->with('url.intended', 'bar')->andReturn('bar');
        $response = $this->redirect->intended('bar');
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRefreshRedirectToCurrentUrl()
    {
        $this->request->shouldReceive('path')->andReturn('http://foo.com/bar');
        $response = $this->redirect->refresh();
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testBackRedirectToHttpReferer()
    {
        $this->headers->shouldReceive('has')->with('referer')->andReturn(true);
        $this->url->shouldReceive('previous')->andReturn('http://foo.com/bar');
        $response = $this->redirect->back();
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testAwayDoesntValidateTheUrl()
    {
        $response = $this->redirect->away('bar');
        $this->assertEquals('bar', $response->getTargetUrl());
    }

    public function testSecureRedirectToHttpsUrl()
    {
        $response = $this->redirect->secure('bar');
        $this->assertEquals('https://foo.com/bar', $response->getTargetUrl());
    }

    public function testAction()
    {
        $this->url->shouldReceive('action')->with('bar@index', [])->andReturn('http://foo.com/bar');
        $response = $this->redirect->action('bar@index');
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRoute()
    {
        $this->url->shouldReceive('route')->with('home')->andReturn('http://foo.com/bar');
        $this->url->shouldReceive('route')->with('home', [])->andReturn('http://foo.com/bar');

        $response = $this->redirect->route('home');
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());

        $response = $this->redirect->home();
        $this->assertEquals('http://foo.com/bar', $response->getTargetUrl());
    }
}
