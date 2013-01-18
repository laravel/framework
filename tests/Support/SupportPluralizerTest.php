<?php

class SupportPluralizerTest extends PHPUnit_Framework_TestCase {

	public function testBasicUsage()
	{
		$this->assertEquals('children', str_plural('child'));
		$this->assertEquals('tests', str_plural('test'));
		$this->assertEquals('deer', str_plural('deer'));
		$this->assertEquals('child', str_singular('children'));
		$this->assertEquals('test', str_singular('tests'));
		$this->assertEquals('deer', str_singular('deer'));
	}

}