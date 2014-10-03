<?php

use Illuminate\Routing\Annotations\Scanner;

class RoutingAnnotationScannerTest extends PHPUnit_Framework_TestCase {

	public function testProperRouteDefinitionsAreGenerated()
	{
		require_once __DIR__.'/fixtures/annotations/BasicController.php';
		$scanner = Scanner::create(__DIR__.'/fixtures/annotations', 'App\Http\Controllers');
		$definition = $scanner->getRouteDefinitions();

		$this->assertEquals(file_get_contents(__DIR__.'/results/annotation-basic.php'), $definition);
	}

}
