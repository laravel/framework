<?php

class HttpJsonResponseTest extends PHPUnit_Framework_TestCase {

	public function testSetAndRetrieveData()
	{
		$response = new Illuminate\Http\JsonResponse(array('foo' => 'bar'));
		$data = $response->getData();
		$this->assertInstanceOf('StdClass', $data);
		$this->assertEquals('bar', $data->foo);
	}

}
