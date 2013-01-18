<?php

class RoutingControllerInspectorTest extends PHPUnit_Framework_TestCase {

	public function testMethodsAreCorrectlyDetermined()
	{
		$inspector = new Illuminate\Routing\Controllers\Inspector;
		$data = $inspector->getRoutable('RoutingControllerInspectorStub');

		$this->assertEquals(2, count($data));
		$this->assertEquals(array('verb' => 'get', 'uri' => 'foo-bar'), $data['getFooBar']);
		$this->assertEquals(array('verb' => 'post', 'uri' => 'baz'), $data['postBaz']);
	}

}

class RoutingControllerInspectorBaseStub {
	public function getBreeze() {}
}

class RoutingControllerInspectorStub extends RoutingControllerInspectorBaseStub {
	public function getFooBar() {}
	public function postBaz() {}
	protected function getBoom() {}
}