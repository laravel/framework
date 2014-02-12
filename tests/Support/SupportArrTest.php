<?php

use Illuminate\Support\Arr;

class SupportArrTest extends PHPUnit_Framework_TestCase {

	/**
	* Test the Str::build method.
	*
	* @group laravel
	*/
	public function testBuildArrayWorks()
	{
		$this->assertEquals(array('Nicholas Ruunu'), Arr::build(array('Ruunu'), function($key, $value)
		{
			return array($key, 'Nicholas ' . $value);
		}));
	}
}
