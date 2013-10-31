<?php

use Illuminate\Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase {

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


	public function testStringTrimmedOnlyWhereNecessary()
	{
		$this->assertEquals(' Taylor Otwell ', Str::words(' Taylor Otwell ', 3));
		$this->assertEquals(' Taylor...', Str::words(' Taylor Otwell ', 1));
	}


	public function testStringWithoutWordsDoesntProduceError()
	{
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Str::words(' '));
		$this->assertEquals($nbsp, Str::words($nbsp));
	}


	public function testStringMacros()
	{
		Illuminate\Support\Str::macro(__CLASS__, function() { return 'foo'; });
		$this->assertEquals('foo', Str::SupportStrTest());
	}


	public function testStartsWith()
	{
		$this->assertTrue(Str::startsWith('jason', 'jas'));
		$this->assertTrue(Str::startsWith('jason', array('jas')));
		$this->assertFalse(Str::startsWith('jason', 'day'));
		$this->assertFalse(Str::startsWith('jason', array('day')));
	}


	public function testEndsWith()
	{
		$this->assertTrue(Str::endsWith('jason', 'on'));
		$this->assertTrue(Str::endsWith('jason', array('on')));
		$this->assertFalse(Str::endsWith('jason', 'no'));
		$this->assertFalse(Str::endsWith('jason', array('no')));
	}


	public function testStrContains()
	{
		$this->assertTrue(Str::contains('taylor', 'ylo'));
		$this->assertTrue(Str::contains('taylor', array('ylo')));
		$this->assertFalse(Str::contains('taylor', 'xxx'));
		$this->assertFalse(Str::contains('taylor', array('xxx')));
	}


	public function testParseCallback()
	{
		$this->assertEquals(array('Class', 'method'), Str::parseCallback('Class@method', 'foo'));
		$this->assertEquals(array('Class', 'foo'), Str::parseCallback('Class', 'foo'));
	}


	public function testFinish()
	{
		$this->assertEquals('abbc', Str::finish('ab', 'bc'));
		$this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
	}

}
