<?php

use Mockery as m;
use Illuminate\Support\Contracts\JsonableInterface;

class HttpResponseTest extends TestCase {

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
		$response->setContent(array('foo' => 'bar'));
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
		$arr = array('foo' => 'bar');
		$response = new Illuminate\Http\Response();
		$response->setContent($arr);
		$this->assertSame($arr, $response->getOriginalContent());
	}


	public function testSetAndRetrieveStatusCode()
	{
		$response = new Illuminate\Http\Response('foo', 404);
		$this->assertSame(404, $response->getStatusCode());

		$response = new Illuminate\Http\Response('foo');
		$response->setStatusCode(404);
		$this->assertSame(404, $response->getStatusCode());
	}

}

class JsonableStub implements JsonableInterface {
	public function toJson($options = 0) { return 'foo'; }
}
