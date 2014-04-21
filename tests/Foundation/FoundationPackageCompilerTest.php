<?php

class FoundationPackageCompilerTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$this->app = new Illuminate\Foundation\Application;
		$this->files = new Illuminate\Filesystem\Filesystem;
	}


	public function testAlreadyCompiledProviderReturnsEmptyArray()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files, 'FoundationPackageCompilerTest.php');
		$this->assertEquals(array(), $compiler->compile(new PackageCompilerServiceProviderStub($this->app)));
	}


	public function testCompilesAllFiles()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);
		$provider->setCompiles(array('stubs/**/*'));

		$expected = array(
			__DIR__.'/stubs/Foo/Bar.php',
			__DIR__.'/stubs/Foo/Bar/Baz.php',
			__DIR__.'/stubs/Foo.php'
		);

		$actual = $compiler->compile($provider);

		sort($expected);
		sort($actual);

		$this->assertEquals($expected, $actual);
	}


	public function testCompilesDirectoryRecursively()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);
		$provider->setCompiles(array('stubs/Foo/**/*'));

		$expected = array(
			__DIR__.'/stubs/Foo/Bar.php',
			__DIR__.'/stubs/Foo/Bar/Baz.php'
		);

		$actual = $compiler->compile($provider);

		sort($expected);
		sort($actual);

		$this->assertEquals($expected, $actual);
	}


	public function testCompilesDirectory()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);
		
		$provider->setCompiles(array('stubs/Foo/*'));
		$this->assertEquals(array(__DIR__.'/stubs/Foo/Bar.php'), $compiler->compile($provider));

		$provider->setCompiles(array('stubs/Foo'));
		$this->assertEquals(array(__DIR__.'/stubs/Foo/Bar.php'), $compiler->compile($provider));
	}


	public function testCompilesFiles()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);
		$provider->setCompiles(array('stubs/Foo.php', 'stubs/Foo/Bar.php'));

		$expected = array(
			__DIR__.'/stubs/Foo.php',
			__DIR__.'/stubs/Foo/Bar.php'
		);

		$actual = $compiler->compile($provider);

		sort($expected);
		sort($actual);

		$this->assertEquals($expected, $actual);
	}


	public function testMissingFilesAreIgnored()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);

		$provider->setCompiles(array('stubs/Bar.php'));
		$this->assertEquals(array(), $compiler->compile($provider));

		$provider->setCompiles(array('stubs/Bar/**/*'));
		$this->assertEquals(array(), $compiler->compile($provider));

		$provider->setCompiles(array('stubs/Bar/*'));
		$this->assertEquals(array(), $compiler->compile($provider));
	}


	public function testServiceProviderIsIgnored()
	{
		$compiler = new Illuminate\Foundation\PackageCompiler($this->files);
		$provider = new PackageCompilerServiceProviderStub($this->app);
		$provider->setCompiles(array('FoundationPackageCompilerTest.php'));

		$this->assertEquals(array(), $compiler->compile($provider));
	}


}


class PackageCompilerServiceProviderStub extends Illuminate\Support\ServiceProvider {

	protected $compiles = array();

	public function register() {}

	public function compiles()
	{
		return $this->compiles;
	}

	public function setCompiles(array $compiles)
	{
		$this->compiles = $compiles;
	}

}
