<?php

namespace Illuminate\Tests\Http;

use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use JsonSerializable;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class HttpResponseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testJsonResponsesAreConvertedAndHeadersAreSet()
    {
        $response = new Response(new ArrayableStub);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonableStub);
        $this->assertSame('foo', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableAndJsonableStub);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response;
        $response->setContent(['foo' => 'bar']);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new JsonSerializableStub);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response = new Response(new ArrayableStub);
        $this->assertSame('{"foo":"bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $response->setContent('{"foo": "bar"}');
        $this->assertSame('{"foo": "bar"}', $response->getContent());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testRenderablesAreRendered()
    {
        $mock = m::mock(Renderable::class);
        $mock->shouldReceive('render')->once()->andReturn('foo');
        $response = new Response($mock);
        $this->assertSame('foo', $response->getContent());
    }

    public function testHeader()
    {
        $response = new Response;
        $this->assertNull($response->headers->get('foo'));
        $response->header('foo', 'bar');
        $this->assertSame('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz', false);
        $this->assertSame('bar', $response->headers->get('foo'));
        $response->header('foo', 'baz');
        $this->assertSame('baz', $response->headers->get('foo'));
    }

    public function testWithCookie()
    {
        $response = new Response;
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertEquals($response, $response->withCookie(new Cookie('foo', 'bar')));
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertSame('foo', $cookies[0]->getName());
        $this->assertSame('bar', $cookies[0]->getValue());
    }

    public function testResponseCookiesInheritRequestSecureState()
    {
        $cookie = Cookie::create('foo', 'bar');

        $response = new Response('foo');
        $response->headers->setCookie($cookie);

        $request = Request::create('/', 'GET');
        $response->prepare($request);

        $this->assertFalse($cookie->isSecure());

        $request = Request::create('https://localhost/', 'GET');
        $response->prepare($request);

        $this->assertTrue($cookie->isSecure());
    }

    public function testGetOriginalContent()
    {
        $arr = ['foo' => 'bar'];
        $response = new Response;
        $response->setContent($arr);
        $this->assertSame($arr, $response->getOriginalContent());
    }

    public function testGetOriginalContentRetrievesTheFirstOriginalContent()
    {
        $previousResponse = new Response(['foo' => 'bar']);
        $response = new Response($previousResponse);

        $this->assertSame(['foo' => 'bar'], $response->getOriginalContent());
    }

    public function testSetAndRetrieveStatusCode()
    {
        $response = new Response('foo');
        $response->setStatusCode(404);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testSetStatusCodeAndRetrieveStatusText()
    {
        $response = new Response('foo');
        $response->setStatusCode(404);
        $this->assertSame('Not Found', $response->statusText());
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

    public function testWithHeaders()
    {
        $response = new Response(null, 200, ['foo' => 'bar']);
        $this->assertSame('bar', $response->headers->get('foo'));

        $response->withHeaders(['foo' => 'BAR', 'bar' => 'baz']);
        $this->assertSame('BAR', $response->headers->get('foo'));
        $this->assertSame('baz', $response->headers->get('bar'));

        $responseMessageBag = new ResponseHeaderBag(['bar' => 'BAZ', 'titi' => 'toto']);
        $response->withHeaders($responseMessageBag);
        $this->assertSame('BAZ', $response->headers->get('bar'));
        $this->assertSame('toto', $response->headers->get('titi'));

        $headerBag = new HeaderBag(['bar' => 'BAAA', 'titi' => 'TATA']);
        $response->withHeaders($headerBag);
        $this->assertSame('BAAA', $response->headers->get('bar'));
        $this->assertSame('TATA', $response->headers->get('titi'));
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

    public function testOkHelper()
    {
        $response = Response::ok();
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreatedHelper()
    {
        $response = Response::created();
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testAcceptedHelper()
    {
        $response = Response::accepted();
        $this->assertSame(202, $response->getStatusCode());
    }

    public function testNoContentHelper()
    {
        $response = Response::noContent();
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testMovePermanentlyHelper()
    {
        $response = Response::movePermanently();
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testFoundHelper()
    {
        $response = Response::found();
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testNotModifiedHelper()
    {
        $response = Response::notModified();
        $this->assertSame(304, $response->getStatusCode());
    }

    public function testTemporaryRedirectHelper()
    {
        $response = Response::temporaryRedirect();
        $this->assertSame(307, $response->getStatusCode());
    }

    public function testPermanentRedirectHelper()
    {
        $response = Response::permanentRedirect();
        $this->assertSame(308, $response->getStatusCode());
    }

    public function testBadRequestHelper()
    {
        $response = Response::badRequest();
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUnauthorizedHelper()
    {
        $response = Response::unauthorized();
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testPaymentRequiredHelper()
    {
        $response = Response::paymentRequired();
        $this->assertSame(402, $response->getStatusCode());
    }

    public function testForbiddenHelper()
    {
        $response = Response::forbidden();
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testNotFoundHelper()
    {
        $response = Response::notFound();
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMethodNotAllowedHelper()
    {
        $response = Response::methodNotAllowed();
        $this->assertSame(405, $response->getStatusCode());
    }

    public function testNotAcceptableHelper()
    {
        $response = Response::notAcceptable();
        $this->assertSame(406, $response->getStatusCode());
    }

    public function testRequestTimeoutHelper()
    {
        $response = Response::requestTimeout();
        $this->assertSame(408, $response->getStatusCode());
    }

    public function testConflictHelper()
    {
        $response = Response::conflict();
        $this->assertSame(409, $response->getStatusCode());
    }

    public function testGoneHelper()
    {
        $response = Response::gone();
        $this->assertSame(410, $response->getStatusCode());
    }

    public function testUnsupportedMediaTypeHelper()
    {
        $response = Response::unsupportedMediaType();
        $this->assertSame(415, $response->getStatusCode());
    }

    public function testUnprocessableEntityHelper()
    {
        $response = Response::unprocessableEntity();
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testTooManyRequestsHelper()
    {
        $response = Response::tooManyRequests();
        $this->assertSame(429, $response->getStatusCode());
    }

    public function testInternalServerErrorHelper()
    {
        $response = Response::internalServerError();
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testServiceUnavailableHelper()
    {
        $response = Response::serviceUnavailable();
        $this->assertSame(503, $response->getStatusCode());
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
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}
