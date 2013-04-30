<?php

use Illuminate\Str\Str;

class StrTest extends PHPUnit_Framework_TestCase
{

		/**
		 * Setup the test environment.
		 */
		public function setUp()
		{
			$this->str = new Str();
		}

		/**
		* Test the $this->str->words method.
		*
		* @group laravel
		*/
		public function testStringCanBeLimitedByWords()
		{
			$this->assertEquals('Taylor...', $this->str->words('Taylor Otwell', 1));
			$this->assertEquals('Taylor___', $this->str->words('Taylor Otwell', 1, '___'));
			$this->assertEquals('Taylor Otwell', $this->str->words('Taylor Otwell', 3));
		}


		public function testStringMacros()
		{
			$this->str->macro(__CLASS__, function() { return 'foo'; });
			$this->assertEquals('foo', $this->str->StrTest());
		}


		public function testAscii()
		{
			$this->assertEquals('hello world', $this->str->ascii('hello world'));
			$this->assertEquals('hello world', $this->str->ascii('ηέllο ώορlδ'));
			$this->assertEquals('geistergrosse', $this->str->ascii('geistergröße'));
			$this->assertEquals('privet mir', $this->str->ascii('привет мир'));
		}


		public function testSlug()
		{
			$this->assertEquals('hello-world', $this->str->slug('hello world'));
			$this->assertEquals('hello-world', $this->str->slug('ηέllο ώορlδ'));
			$this->assertEquals('geistergrosse', $this->str->slug('geistergröße'));
			$this->assertEquals('privet-mir', $this->str->slug('привет мир'));
		}


		public function testRemoveList()
		{
			$str = new Str();
			$str->setRemoveList(array('and'));
			$this->assertEquals('hello-world', $str->slug('hello and world'));
		}


		public function testLanguageSpecificMap()
		{
			$str = new Str();
			$str->setLanguage('de');
			$this->assertEquals('geistergroesse', $str->slug('geistergröße'));
		}
}
