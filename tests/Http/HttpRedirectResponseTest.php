<?php

namespace Illuminate\Tests\Http;

use Mockery as m;
use BadMethodCallException;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Support\MessageProvider;

class HttpRedirectResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testHeaderOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $this->assertNull($response->headers->get('foo'));
        $response->header('foo', 'bar');
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz', false);
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz');
        $this->assertEquals('baz', $response->headers->get('foo'));
    }

    public function testWithOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flash')->twice();
        $response->with(['name', 'age']);
    }

    public function testWithCookieOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertEquals($response, $response->withCookie(new Cookie('foo', 'bar')));
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('foo', $cookies[0]->getName());
        $this->assertEquals('bar', $cookies[0]->getValue());
    }

    public function testInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor', 'age' => 26]);
        $response->withInput();
    }

    public function testOnlyInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->onlyInput('name');
    }

    public function testExceptInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->exceptInput('age');
    }

    public function testFlashingErrorsOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('get')->with('errors', m::type(ViewErrorBag::class))->andReturn(new ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type(ViewErrorBag::class));
        $provider = m::mock(MessageProvider::class);
        $provider->shouldReceive('getMessageBag')->once()->andReturn(new MessageBag);
        $response->withErrors($provider);
    }

    public function testSettersGettersOnRequest()
    {
        $response = new RedirectResponse('foo.bar');
        $this->assertNull($response->getRequest());
        $this->assertNull($response->getSession());

        $request = Request::create('/', 'GET');
        $session = m::mock(Store::class);
        $response->setRequest($request);
        $response->setSession($session);
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($session, $response->getSession());
    }

    public function testRedirectWithErrorsArrayConvertsToMessageBag()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('get')->with('errors', m::type(ViewErrorBag::class))->andReturn(new ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type(ViewErrorBag::class));
        $provider = ['foo' => 'bar'];
        $response->withErrors($provider);
    }

    public function testMagicCall()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock(Store::class));
        $session->shouldReceive('flash')->once()->with('foo', 'bar');
        $response->withFoo('bar');
    }

    public function testMagicCallException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Call to undefined method Illuminate\Http\RedirectResponse::doesNotExist()');

        $response = new RedirectResponse('foo.bar');
        $response->doesNotExist('bar');
    }
}
