<?php

use Illuminate\Support\Str;

class SupportStrTest extends TestCase {

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


	public function testStringTitle()
	{
		$this->assertEquals('Jefferson Costella', Str::title('jefferson costella'));
		$this->assertEquals('Jefferson Costella', Str::title('jefFErson coSTella'));
	}


	public function testStringWithoutWordsDoesntProduceError()
	{
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Str::words(' '));
		$this->assertEquals($nbsp, Str::words($nbsp));
	}


	public function testStartsWith()
	{
		$this->assertTrue(Str::startsWith('jason', 'jas'));
		$this->assertTrue(Str::startsWith('jason', 'jason'));
		$this->assertTrue(Str::startsWith('jason', array('jas')));
		$this->assertFalse(Str::startsWith('jason', 'day'));
		$this->assertFalse(Str::startsWith('jason', array('day')));
		$this->assertFalse(Str::startsWith('jason', ''));
	}


	public function testEndsWith()
	{
		$this->assertTrue(Str::endsWith('jason', 'on'));
		$this->assertTrue(Str::endsWith('jason', 'jason'));
		$this->assertTrue(Str::endsWith('jason', array('on')));
		$this->assertFalse(Str::endsWith('jason', 'no'));
		$this->assertFalse(Str::endsWith('jason', array('no')));
		$this->assertFalse(Str::endsWith('jason', ''));
		$this->assertFalse(Str::endsWith('7', ' 7'));
	}


	public function testStrContains()
	{
		$this->assertTrue(Str::contains('taylor', 'ylo'));
		$this->assertTrue(Str::contains('taylor', array('ylo')));
		$this->assertFalse(Str::contains('taylor', 'xxx'));
		$this->assertFalse(Str::contains('taylor', array('xxx')));
		$this->assertFalse(Str::contains('taylor', ''));
	}


	public function testParseCallback()
	{
		$this->assertEquals(array('Class', 'method'), Str::parseCallback('Class@method', 'foo'));
		$this->assertEquals(array('Class', 'foo'), Str::parseCallback('Class', 'foo'));
	}


	public function testSlug()
	{
		$this->assertEquals('hello-world', Str::slug('hello world'));
		$this->assertEquals('hello-world', Str::slug('hello-world'));
		$this->assertEquals('hello-world', Str::slug('hello_world'));
		$this->assertEquals('hello_world', Str::slug('hello_world', '_'));
	}


	public function testFinish()
	{
		$this->assertEquals('abbc', Str::finish('ab', 'bc'));
		$this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
		$this->assertEquals('abcbbc', Str::finish('abcbbcbc', 'bc'));
	}


	public function testIs()
	{
		$this->assertTrue(Str::is('/', '/'));
		$this->assertFalse(Str::is('/', ' /'));
		$this->assertFalse(Str::is('/', '/a'));
		$this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));
		$this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));
	}


	public function testLower()
	{
		$this->assertEquals('foo bar baz', Str::lower('FOO BAR BAZ'));
		$this->assertEquals('foo bar baz', Str::lower('fOo Bar bAz'));
	}


	public function testUpper()
	{
		$this->assertEquals('FOO BAR BAZ', Str::upper('foo bar baz'));
		$this->assertEquals('FOO BAR BAZ', Str::upper('foO bAr BaZ'));
	}


	public function testLimit()
	{
		$this->assertEquals('Laravel is...', Str::limit('Laravel is a free, open source PHP web application framework.', 10));
	}


	public function testLength()
	{
		$this->assertEquals(11, Str::length('foo bar baz'));
	}


	public function testQuickRandom()
	{
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Str::quickRandom($randomInteger)));
		$this->assertInternalType('string', Str::quickRandom());
		$this->assertEquals(16, strlen(Str::quickRandom()));
	}


	public function testRandom()
	{
		$this->assertEquals(16, strlen(Str::random()));
		$randomInteger = mt_rand(1, 100);
		$this->assertEquals($randomInteger, strlen(Str::random($randomInteger)));
		$this->assertInternalType('string', Str::random());
	}

}
