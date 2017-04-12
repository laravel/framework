<?php

use Illuminate\Html\HtmlBuilder;

class HtmlBuilderTest extends PHPUnit_Framework_TestCase {

	public function testDateTime()
	{
		$html = new HtmlBuilder;
		$dt = new \DateTime('2014-01-01 12:00:00');
		$this->assertEquals('<time datetime="2014-01-01T12:00:00+00:00">January 1</time>', $html->dateTime($dt, 'F j'));
	}

}