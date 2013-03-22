<?php

use Mockery as m;
use Illuminate\Support\ClassLoader;

class SupportClassLoaderTest extends PHPUnit_Framework_TestCase {

	public function testNormalizingClass()
	{
		$php53Class      = 'Foo\Bar\Baz\Bat';
		$prefixed53Class = '\Foo\Bar\Baz\Bat';
		$php52Class      = 'Foo_Bar_Baz_Bat';
		$expected = 'Foo'.DIRECTORY_SEPARATOR.'Bar'.DIRECTORY_SEPARATOR.'Baz'.DIRECTORY_SEPARATOR.'Bat.php';

		$this->assertEquals($expected, ClassLoader::normalizeClass($php53Class));
		$this->assertEquals($expected, ClassLoader::normalizeClass($prefixed53Class));
		$this->assertEquals($expected, ClassLoader::normalizeClass($php52Class));
	}


	public function testManipulatingDirectories()
	{
		ClassLoader::removeDirectories();
		$this->assertEmpty(ClassLoader::getDirectories());
		ClassLoader::addDirectories($directories = array('foo', 'bar'));
		$this->assertEquals($directories, ClassLoader::getDirectories());
		ClassLoader::addDirectories('baz');
		$this->assertEquals(array_merge($directories, array('baz')), ClassLoader::getDirectories());
		ClassLoader::removeDirectories('baz');
		$this->assertEquals($directories, ClassLoader::getDirectories());
		ClassLoader::removeDirectories($directories);
		$this->assertEmpty(ClassLoader::getDirectories());
	}


	public function testClassLoadingWorks()
	{
		$php53Class = 'Foo\Bar\Php53';
		$php52Class = 'Foo_Bar_Php52';

		ClassLoader::addDirectories($directory = __DIR__.'/stubs/psr');
		$this->assertTrue(ClassLoader::load($php53Class));
		$this->assertTrue(ClassLoader::load($php52Class));
		ClassLoader::removeDirectories($directory);
	}

}
