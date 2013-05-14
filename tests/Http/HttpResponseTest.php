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
	}


	public function testRenderablesAreRendered()
	{
		$mock = m::mock('Illuminate\Support\Contracts\RenderableInterface');
		$mock->shouldReceive('render')->once()->andReturn('foo');
		$response = new Illuminate\Http\Response($mock);
		$this->assertEquals('foo', $response->getContent());		
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

    public function testRedirectWithErrorsArrayConvertsToMessageBag()
    {
        $response = new RedirectResponse('foo.bar');
        $response->setRequest(Request::create('/', 'GET', array('name' => 'Taylor', 'age' => 26)));
        $response->setSession($session = m::mock('Illuminate\Session\Store'));
        $session->shouldReceive('flash')->once()->with('errors', m::type('Illuminate\Support\MessageBag'));
        $provider = array('foo' => 'bar');
        $response->withErrors($provider);
    }

}

class JsonableStub implements JsonableInterface {
	public function toJson($options = 0) { return 'foo'; }
}