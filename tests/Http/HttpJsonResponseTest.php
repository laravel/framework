<?php

class HttpJsonResponseTest extends PHPUnit_Framework_TestCase {

	public function testSetAndRetrieveData()
	{
		$response = new Illuminate\Http\JsonResponse(array('foo' => 'bar'));
		$data = $response->getData();
		$this->assertInstanceOf('StdClass', $data);
		$this->assertEquals('bar', $data->foo);
	}


	public function testSetAndRetrieveOptions()
	{
		$response = new Illuminate\Http\JsonResponse(['foo' => 'bar']);
		$response->setJsonOptions(JSON_PRETTY_PRINT);
		$this->assertSame(JSON_PRETTY_PRINT, $response->getJsonOptions());
	}

}
