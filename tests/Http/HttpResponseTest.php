<?php

use Mockery as m;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Contracts\JsonableInterface;

class HttpResponseTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testJsonResponsesAreConvertedAndHeadersAreSet()
	{
		$response = new Illuminate\Http\Response(new JsonableStub);
		$this->assertEquals('foo', $response->getContent());
		$this->assertEquals('application/json', $response->headers->get('Content-Type'));
		
		$response = new Illuminate\Http\Response();
		$response->setContent(array('foo'=>'bar'));
		$this->assertEquals('{"foo":"bar"}', $response->getContent());
		$this->assertEquals('application/json', $response->headers->get('Content-Type'));
	}


	public function testRenderablesAreRendered()
	{
		$mock = m::mock('Illuminate\Support\Contracts\RenderableInterface');
		$mock->shouldReceive('render')->once()->andReturn('foo');
		$response = new Illuminate\Http\Response($mock);
		$this->assertEquals('foo', $response->getContent());		
	}
	
	
	public function testHeader()
	{
		$response = new Illuminate\Http\Response();
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
		$response = new Illuminate\Http\Response();
		$this->assertEquals(0, count($response->headers->getCookies()));
		$this->assertEquals($response, $response->withCookie(new \Symfony\Component\HttpFoundation\Cookie('foo', 'bar')));
		$cookies = $response->headers->getCookies();
		$this->assertEquals(1, count($cookies));
		$this->assertEquals('foo', $cookies[0]->getName());
		$this->assertEquals('bar', $cookies[0]->getValue());
	}
	
	
	public function testGetOriginalContent()
	{
		$arr = array('foo'=>'bar');
		$response = new Illuminate\Http\Response();
		$response->setContent($arr);
		$this->assertTrue($arr === $response->getOriginalContent());
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
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->twice();
		$response->with(array('name', 'age'));
	}
	
	
	public function testWithCookieOnRedirect()
	{
		$response = new RedirectResponse('foo.bar');
		$this->assertEquals(0, count($response->headers->getCookies()));
		$this->assertEquals($response, $response->withCookie(new \Symfony\Component\HttpFoundation\Cookie('foo', 'bar')));
		$cookies = $response->headers->getCookies();
		$this->assertEquals(1, count($cookies));
		$this->assertEquals('foo', $cookies[0]->getName());
		$this->assertEquals('bar', $cookies[0]->getValue());
	}


    public function testInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flashInput')->once()->with(array('name' => 'Taylor', 'age' => 26));
        $response->withInput();
    }


    public function testOnlyInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flashInput')->once()->with(array('name' => 'Taylor'));
        $response->onlyInput('name');
    }


    public function testExceptInputOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flashInput')->once()->with(array('name' => 'Taylor'));
        $response->exceptInput('age');
    }


    public function testFlashingErrorsOnRedirect()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->once()->with('errors', array('foo' => 'bar'));
        $provider = m::mock('Illuminate\Support\Contracts\MessageProviderInterface');
        $provider->shouldReceive('getMessageBag')->once()->andReturn(array('foo' => 'bar'));
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
		$this->assertTrue($request === $response->getRequest());
		$this->assertTrue($session === $response->getSession());
	}

	
    public function testRedirectWithErrorsArrayConvertsToMessageBag()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->once()->with('errors', m::type('Illuminate\Support\MessageBag'));
        $provider = array('foo' => 'bar');
        $response->withErrors($provider);
    }
	
	
	public function testMagicCall()
	{
		$response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->once()->with('foo', 'bar');
		$response->withFoo('bar');
	}


	public function testMagicCallException()
	{
		$this->setExpectedException('BadMethodCallException');
		$response = new RedirectResponse('foo.bar');
		$response->doesNotExist('bar');
	}

}

class JsonableStub implements JsonableInterface {
	public function toJson($options = 0) { return 'foo'; }
}