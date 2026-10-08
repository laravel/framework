<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\Store;
use JMac\Testing\Double;
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
        $this->headers = new HeaderBag;

        $this->request = Request::create('/', 'GET');
        $this->request->setRouteResolver(fn () => true);
        $this->request->headers = $this->headers;

        $this->url = Double::for(UrlGenerator::class);
        $this->url->allows('getRequest')->returns($this->request);
        $this->url->allows('to')->with('bar', [], null)->returns('http://foo.com/bar');
        $this->url->allows('to')->with('bar', [], true)->returns('https://foo.com/bar');
        $this->url->allows('to')->with('login', [], null)->returns('http://foo.com/login');
        $this->url->allows('to')->with('http://foo.com/bar', [], null)->returns('http://foo.com/bar');
        $this->url->allows('to')->with('/', [], null)->returns('http://foo.com/');
        $this->url->allows('to')->with('http://foo.com/bar?signature=secret', [], null)->returns('http://foo.com/bar?signature=secret');

        $this->session = new Store('test', new NullSessionHandler);

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
        $this->url->expects('full')->returns('http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
        $this->assertSame('http://foo.com/bar', $this->session->get('url.intended'));
    }

    public function testGuestPutPreviousUrlInSession()
    {
        $this->request->setMethod('POST');
        $this->url->expects('previous')->returns('http://foo.com/bar');

        $response = $this->redirect->guest('login');

        $this->assertSame('http://foo.com/login', $response->getTargetUrl());
        $this->assertSame('http://foo.com/bar', $this->session->get('url.intended'));
    }

    public function testIntendedRedirectToIntendedUrlInSession()
    {
        $this->session->put('url.intended', 'http://foo.com/bar');

        $response = $this->redirect->intended();

        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
        $this->assertNull($this->session->get('url.intended'));
    }

    public function testIntendedWithoutIntendedUrlInSession()
    {
        // without fallback url
        $response = $this->redirect->intended();
        $this->assertSame('http://foo.com/', $response->getTargetUrl());

        // with a fallback url
        $response = $this->redirect->intended('bar');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRefreshRedirectToCurrentUrl()
    {
        $this->url->expects('getRequest')->returns(Request::create('/bar'));

        $response = $this->redirect->refresh();
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testBackRedirectToHttpReferer()
    {
        $this->url->expects('previous')->returns('http://foo.com/bar');
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
        $this->url->expects('action')->with('bar@index', [])->returns('http://foo.com/bar');
        $response = $this->redirect->action('bar@index');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testRoute()
    {
        $this->url->expects('route')->with('home', [])->returns('http://foo.com/bar');

        $response = $this->redirect->route('home');
        $this->assertSame('http://foo.com/bar', $response->getTargetUrl());
    }

    public function testSignedRoute()
    {
        $this->url->expects('signedRoute')->with('home', [], null)->returns('http://foo.com/bar?signature=secret');

        $response = $this->redirect->signedRoute('home');
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function testTemporarySignedRoute()
    {
        $this->url->expects('temporarySignedRoute')->with('home', 10, [])->returns('http://foo.com/bar?signature=secret');

        $response = $this->redirect->temporarySignedRoute('home', 10);
        $this->assertSame('http://foo.com/bar?signature=secret', $response->getTargetUrl());
    }

    public function testItSetsAndGetsValidIntendedUrl()
    {
        $result = $this->redirect->setIntendedUrl('http://foo.com/bar');
        $this->assertInstanceOf(Redirector::class, $result);

        $this->assertSame('http://foo.com/bar', $this->redirect->getIntendedUrl());
    }
}
