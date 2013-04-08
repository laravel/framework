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
			$this->assertEquals('Hello...', Str::words('Hello World', 1));
			$this->assertEquals('Hello___', Str::words('Hello World', 1, '___'));
			$this->assertEquals('Hello World', Str::words('Hello World', 3));
		}


		public function testStringContainsSubstring()
		{
			$this->assertTrue(Str::contains('Hello World', 'Hello'));
		}


		public function testStringEndsWith()
		{
			$this->assertTrue(Str::endsWith('Hello World', 'World'));
		}


		public function testStringJoin()
		{
			$join = Str::join(' ', array('Hello World'));

			$this->assertEquals('Hello World', $join);
		}


		public function testStringSplit()
		{
			$s = Str::split(' ', 'Hello World');

			$this->assertEquals(array('Hello', 'World'), $s);
		}


		public function testStringStartsWith()
		{
			$this->assertTrue(Str::startsWith('Hello World', 'Hello'));
		}


		public function testStringMacros()
		{
			Illuminate\Support\Str::macro(__CLASS__, function() { return 'foo'; });
			$this->assertEquals('foo', Str::SupportStrTest());
		}

}
