<?php

namespace Illuminate\Tests\Http;

use Mockery as m;
use JsonSerializable;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class HttpResponseTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testJsonResponsesAreConvertedAndHeadersAreSet()
    {
        $response = new \Illuminate\Http\Response(new ArrayableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new \Illuminate\Http\Response(new JsonableStub);
        $this->assertEquals('foo', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new \Illuminate\Http\Response(new ArrayableAndJsonableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new \Illuminate\Http\Response();
        $response->setContent(['foo' => 'bar']);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response = new \Illuminate\Http\Response(new JsonSerializableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testResponseHeaderTypeIsReset()
    {
        $response = new \Illuminate\Http\Response(new ArrayableStub);
        $this->assertEquals('{"foo":"bar"}', $response->getContent());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $response->setContent('foo');
        $this->assertEquals('foo', $response->getContent());
        $this->assertNotEquals('application/json', $response->headers->get('Content-Type'));
    }

    public function testRenderablesAreRendered()
    {
        $mock = m::mock('Illuminate\Contracts\Support\Renderable');
        $mock->shouldReceive('render')->once()->andReturn('foo');
        $response = new \Illuminate\Http\Response($mock);
        $this->assertEquals('foo', $response->getContent());
    }

    public function testHeader()
    {
        $response = new \Illuminate\Http\Response();
        $this->assertNull($response->headers->get('foo'));
        $response->header('foo', 'bar');
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz', false);
        $this->assertEquals('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz');
        $this->assertEquals('baz', $response->headers->get('foo'));
    }

    public function testWithCookie()
    {
        $response = new \Illuminate\Http\Response();
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertEquals($response, $response->withCookie(new \Symfony\Component\HttpFoundation\Cookie('foo', 'bar')));
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('foo', $cookies[0]->getName());
        $this->assertEquals('bar', $cookies[0]->getValue());
    }

    public function testGetOriginalContent()
    {
        $arr = ['foo' => 'bar'];
        $response = new \Illuminate\Http\Response();
        $response->setContent($arr);
        $this->assertSame($arr, $response->getOriginalContent());
    }

    public function testSetAndRetrieveStatusCode()
    {
        $response = new \Illuminate\Http\Response('foo');
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testOnlyInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->onlyInput('name');
    }

    public function testExceptInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flashInput')->once()->with(['name' => 'Taylor']);
        $response->exceptInput('age');
    }

    public function testFlashingErrorsOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('get')->with('errors', m::type('Illuminate\Support\ViewErrorBag'))->andReturn(new \Illuminate\Support\ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type('Illuminate\Support\ViewErrorBag'));
        $provider = m::mock('Illuminate\Contracts\Support\MessageProvider');
        $provider->shouldReceive('getMessageBag')->once()->andReturn(new \Illuminate\Support\MessageBag);
        $response->withErrors($provider);
    }

    public function testSettersGettersOnRequest()
    {
        $response = new RedirectResponse('foo.bar');
        $this->assertNull($response->getRequest());
        $this->assertNull($response->getSession());

        $request = Request::create('/', 'GET');
        $session = m::mock('Illuminate\Session\Store');
        $response->setRequest($request);
        $response->setSession($session);
        $this->assertSame($request, $response->getRequest());
        $this->assertSame($session, $response->getSession());
    }

    public function testRedirectWithErrorsArrayConvertsToMessageBag()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('get')->with('errors', m::type('Illuminate\Support\ViewErrorBag'))->andReturn(new \Illuminate\Support\ViewErrorBag);
        $session->shouldReceive('flash')->once()->with('errors', m::type('Illuminate\Support\ViewErrorBag'));
        $provider = ['foo' => 'bar'];
        $response->withErrors($provider);
    }

    public function testWithHeaders()
    {
        $response = new \Illuminate\Http\Response(null, 200, ['foo' => 'bar']);
        $this->assertSame('bar', $response->headers->get('foo'));

        $response->withHeaders(['foo' => 'BAR', 'bar' => 'baz']);
        $this->assertSame('BAR', $response->headers->get('foo'));
        $this->assertSame('baz', $response->headers->get('bar'));

        $responseMessageBag = new \Symfony\Component\HttpFoundation\ResponseHeaderBag(['bar' => 'BAZ', 'titi' => 'toto']);
        $response->withHeaders($responseMessageBag);
        $this->assertSame('BAZ', $response->headers->get('bar'));
        $this->assertSame('toto', $response->headers->get('titi'));

        $headerBag = new \Symfony\Component\HttpFoundation\HeaderBag(['bar' => 'BAAA', 'titi' => 'TATA']);
        $response->withHeaders($headerBag);
        $this->assertSame('BAAA', $response->headers->get('bar'));
        $this->assertSame('TATA', $response->headers->get('titi'));
    }

    public function testMagicCall()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', ['name' => 'Taylor', 'age' => 26]));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->once()->with('foo', 'bar');
        $response->withFoo('bar');
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testMagicCallException()
    {
        $response = new RedirectResponse('foo.bar');
        $response->doesNotExist('bar');
    }
}

class ArrayableStub implements Arrayable
{
    public function toArray()
    {
        return ['foo' => 'bar'];
    }
}

class ArrayableAndJsonableStub implements Arrayable, Jsonable
{
    public function toJson($options = 0)
    {
        return '{"foo":"bar"}';
    }

    public function toArray()
    {
        return [];
    }
}

class JsonableStub implements Jsonable
{
    public function toJson($options = 0)
    {
        return 'foo';
    }
}

class JsonSerializableStub implements JsonSerializable
{
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}
