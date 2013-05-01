<?php

use Illuminate\Str\Str;
use Illuminate\Str\Inflector;

class StrTest extends PHPUnit_Framework_TestCase
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


		public function testStringMacros()
		{
			Str::macro(__CLASS__, function() { return 'foo'; });
			$this->assertEquals('foo', Str::StrTest());
		}


		public function testAscii()
		{
			$this->assertEquals('hello world', Str::ascii('hello world'));
			$this->assertEquals('hello world', Str::ascii('ηέllο ώορlδ'));
			$this->assertEquals('geistergrosse', Str::ascii('geistergröße'));
			$this->assertEquals('privet mir', Str::ascii('привет мир'));
		}


		public function testSlug()
		{
			$this->assertEquals('hello-world', Str::slug('hello world'));
			$this->assertEquals('hello-world', Str::slug('ηέllο ώορlδ'));
			$this->assertEquals('geistergrosse', Str::slug('geistergröße'));
			$this->assertEquals('privet-mir', Str::slug('привет мир'));
		}


		public function testRemoveList()
		{
			$str = new Inflector();
			$str->setRemoveList(array('and'));
			$this->assertEquals('hello-world', $str->slug('hello and world'));
		}


		public function testLanguageSpecificMap()
		{
			$str = new Inflector();
			$str->setLanguage('de');
			$this->assertEquals('geistergroesse', $str->slug('geistergröße'));
		}
}
