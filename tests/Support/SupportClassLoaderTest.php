<?php

use Mockery as m;
use Illuminate\Support\ClassLoader;

class SupportClassLoaderTest extends PHPUnit_Framework_TestCase {

	public function testNormalizingClass()
	{
		$php53Class = 'Foo\Bar\Baz\Bat';
		$php52Class = 'Foo_Bar_Baz_Bat';
		$expected = 'Foo'.DIRECTORY_SEPARATOR.'Bar'.DIRECTORY_SEPARATOR.'Baz'.DIRECTORY_SEPARATOR.'Bat.php';

		$this->assertEquals($expected, ClassLoader::normalizeClass($php53Class));
		$this->assertEquals($expected, ClassLoader::normalizeClass($php52Class));
	}

	/**
	 * We want to run in a separate process to ensure that
	 * we don't possibly interfere with any other potential
	 * tests that may interact with the classloader statically.
	 *
	 * @runInSeparateProcess
	 */
	public function testClassLoadingWorks()
	{
		$php53Class = 'Foo\Bar\Php53';
		$php52Class = 'Foo_Bar_Php52';

		ClassLoader::addDirectories(__DIR__.'/stubs/psr');
		ClassLoader::load($php53Class);
		ClassLoader::load($php52Class);

		$this->assertTrue(class_exists($php53Class));
		$this->assertTrue(class_exists($php52Class));
	}

}
