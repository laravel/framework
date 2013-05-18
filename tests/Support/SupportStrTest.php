<?php

use Illuminate\Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase
{

	/**
	* Test the Str::words method.
	*
	* @group laravel
	*/
	public function testStringCanBeLimitedByWords()
	{
		$this->assertEquals('Taylor...', Str::words('Taylor Otwell', 1));
		$this->assertEquals('Taylor___', Str::words('Taylor Otwell', 1, '___'));
		$this->assertEquals('Taylor Otwell', Str::words('Taylor Otwell', 3));
	}


	public function testBehaviorWithLeadingAndTrailingSpaces()
	{
		$this->assertEquals(' Taylor Otwell', Str::words(' Taylor Otwell ', 3));
		$this->assertEquals(' Taylor...', Str::words(' Taylor Otwell ', 1));
	}


	public function testStringWithoutWordsDoesntProduceError()
	{
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals('', Str::words(' '));
		$this->assertEquals('', Str::words($nbsp));
	}


	public function testStringMacros()
	{
		Illuminate\Support\Str::macro(__CLASS__, function() { return 'foo'; });
		$this->assertEquals('foo', Str::SupportStrTest());
	}

}
