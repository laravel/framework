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


		public function testStringMacros()
		{
			Illuminate\Support\Str::macro(__CLASS__, function() { return 'foo'; });
			$this->assertEquals('foo', Str::SupportStrTest());
		}


		public function testLanguagePriority()
		{
			$this->assertEquals('geistergroesse', Str::ascii('geistergröße', 'de'));
			$this->assertEquals('geistergroesse', Str::slug('geistergröße', '-', 'de'));
		}
}
