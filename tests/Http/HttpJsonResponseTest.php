<?php

use Mockery as m;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\ResponseFactory;

class HttpJsonResponseTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSetAndRetrieveData()
	{
		$response = new JsonResponse(array('foo' => 'bar'));
		$data = $response->getData();
		$this->assertInstanceOf('StdClass', $data);
		$this->assertEquals('bar', $data->foo);
	}


	public function testArrayableSendAsJson()
	{
		$data = m::mock('Illuminate\Support\Contracts\ArrayableInterface');
		$data->shouldReceive('toArray')->andReturn(array('foo' => 'bar'));

		$factory = new ResponseFactory(m::mock('Illuminate\View\Factory'));

		$response = $factory->json($data);
		$this->assertEquals('{"foo":"bar"}', $response->getContent());
	}

}
