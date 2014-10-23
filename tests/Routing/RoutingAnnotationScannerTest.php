<?php

use Illuminate\Routing\Annotations\Scanner;

class RoutingAnnotationScannerTest extends PHPUnit_Framework_TestCase {

	public function testProperRouteDefinitionsAreGenerated()
	{
		require_once __DIR__.'/fixtures/annotations/BasicController.php';
		$scanner = Scanner::create(['App\Http\Controllers\BasicController']);
		$definition = $scanner->getRouteDefinitions();

		$this->assertEquals(trim(file_get_contents(__DIR__.'/results/annotation-basic.php')), $definition);
	}

}
