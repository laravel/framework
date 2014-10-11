<?php

use Mockery as m;
use Illuminate\View\Engines\PhpEngine;

class ViewPhpEngineTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		$this->addToAssertionCount(m::getContainer()->mockery_getExpectationCount());

		m::close();
	}


	public function testViewsMayBeProperlyRendered()
	{
		$engine = new PhpEngine;
		$this->assertEquals("Hello World\n", $engine->get(__DIR__.'/fixtures/basic.php'));
	}

}
