<?php

class RoutingControllerInspectorTest extends PHPUnit_Framework_TestCase {

	public function testMethodsAreCorrectlyDetermined()
	{
		$inspector = new Illuminate\Routing\Controllers\Inspector;
		$data = $inspector->getRoutable('RoutingControllerInspectorStub', 'prefix');

		$this->assertEquals(4, count($data));
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix', 'uri' => 'prefix'), $data['getIndex'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/index', 'uri' => 'prefix/index/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['getIndex'][1]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/foo-bar', 'uri' => 'prefix/foo-bar/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['getFooBar'][0]);
		$this->assertEquals(array('verb' => 'post', 'plain' => 'prefix/baz', 'uri' => 'prefix/baz/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['postBaz'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/breeze', 'uri' => 'prefix/breeze/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['getBreeze'][0]);
	}

	public function testMethodsAreCorrectWhenControllerIsNamespaced()
	{
		$inspector = new Illuminate\Routing\Controllers\Inspector;
		$data = $inspector->getRoutable('\\RoutingControllerInspectorStub', 'prefix');

		$this->assertEquals(4, count($data));
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix', 'uri' => 'prefix'), $data['getIndex'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/index', 'uri' => 'prefix/index/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['getIndex'][1]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/foo-bar', 'uri' => 'prefix/foo-bar/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['getFooBar'][0]);
		$this->assertEquals(array('verb' => 'post', 'plain' => 'prefix/baz', 'uri' => 'prefix/baz/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}'), $data['postBaz'][0]);
	}

}

class RoutingControllerInspectorBaseStub {
	public function getBreeze() {}
}

class RoutingControllerInspectorStub extends RoutingControllerInspectorBaseStub {
	public function getIndex() {}
	public function getFooBar() {}
	public function postBaz() {}
	protected function getBoom() {}
}
