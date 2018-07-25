<?php

class RoutingControllerInspectorTest extends TestCase {

	public function testMethodsAreCorrectlyDetermined()
	{
		$inspector = new Illuminate\Routing\ControllerInspector;
		$data = $inspector->getRoutable('RoutingControllerInspectorStub', 'prefix');

		$this->assertEquals(4, count($data));
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix', 'uri' => 'prefix'), $data['getIndex'][1]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/index', 'uri' => 'prefix/index/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['getIndex'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/foo-bar', 'uri' => 'prefix/foo-bar/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['getFooBar'][0]);
		$this->assertEquals(array('verb' => 'post', 'plain' => 'prefix/baz', 'uri' => 'prefix/baz/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['postBaz'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/breeze', 'uri' => 'prefix/breeze/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['getBreeze'][0]);
	}


	public function testMethodsAreCorrectWhenControllerIsNamespaced()
	{
		$inspector = new Illuminate\Routing\ControllerInspector;
		$data = $inspector->getRoutable('\\RoutingControllerInspectorStub', 'prefix');

		$this->assertEquals(4, count($data));
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix', 'uri' => 'prefix'), $data['getIndex'][1]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/index', 'uri' => 'prefix/index/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['getIndex'][0]);
		$this->assertEquals(array('verb' => 'get', 'plain' => 'prefix/foo-bar', 'uri' => 'prefix/foo-bar/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['getFooBar'][0]);
		$this->assertEquals(array('verb' => 'post', 'plain' => 'prefix/baz', 'uri' => 'prefix/baz/{one?}/{two?}/{three?}/{four?}/{five?}'), $data['postBaz'][0]);
	}

}

class RoutingControllerInspectorBaseStub {
	public function getBreeze() {}
	private function patchTest() {}
}

class RoutingControllerInspectorStub extends RoutingControllerInspectorBaseStub {
	public function getIndex() {}
	public function getFooBar() {}
	public function postBaz() {}
	protected function getBoom() {}
	private function putTest() {}
}
