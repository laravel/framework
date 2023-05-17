<?php

namespace Illuminate\Tests\Http;

use BadMethodCallException;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

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
        $this->assertSame('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz', false);
        $this->assertSame('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz');
        $this->assertSame('baz', $response->headers->get('foo'));
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
        $this->assertSame('foo', $cookies[0]->getName());
        $this->assertSame('bar', $cookies[0]->getValue());
    }

    public function testFragmentIdentifierOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');

        $response->withFragment('foo');
        $this->assertSame('foo', parse_url($response->getTargetUrl(), PHP_URL_FRAGMENT));

        $response->withFragment('#bar');
        $this->assertSame('bar', parse_url($response->getTargetUrl(), PHP_URL_FRAGMENT));

        $response->withoutFragment();
        $this->assertNull(parse_url($response->getTargetUrl(), PHP_URL_FRAGMENT));
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
