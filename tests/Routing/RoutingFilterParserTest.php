<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;

class RoutingFilterParserTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFiltersAreParsedCorrectlyByClass()
	{
		$reader = $this->getParser();

		$controller = new FilerParserTestControllerStub;
		$controller->beforeFilter('code-before');
		$controller->beforeFilter('code-before-2', array('only' => 'fooAction'));
		$controller->beforeFilter('code-before-3', array('except' => 'fooAction'));
		$controller->beforeFilter('code-before-4', array('on' => 'post'));
		$controller->afterFilter('code-after');

		$request = Request::create('/', 'GET');

		$filters = $reader->parse($controller, $request, 'fooAction', 'Illuminate\Routing\Controllers\Before');

		$this->assertEquals(2, count($filters));
		$this->assertEquals(array('code-before', 'code-before-2'), $filters);
	}


	protected function getParser()
	{
		return new Illuminate\Routing\Controllers\FilterParser;
	}

}

class FilerParserTestControllerStub extends Illuminate\Routing\Controllers\Controller {
	
}