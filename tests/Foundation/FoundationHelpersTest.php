<?php

class FoundationHelpersTest extends PHPUnit_Framework_TestCase {

	public function testElixir()
	{
		// set fake public path to fixtures dir
		app()->instance('path.public', __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'public');

		// this should be set to a dir where it CANNOT find the elixir.json file
		app()->instance('path.base', __DIR__);

		// regular usage without considering elixir config
		$assetPath = elixir('test.css');
		$this->assertEquals('/build/test-123.css', $assetPath);
	}

	public function testElixirWithBuildPath()
	{
		// set fake public path to fixtures dir
		app()->instance('path.public', __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'public');

		// this should be set to a dir where it CAN find the elixir.json file
		app()->instance('path.base', __DIR__.DIRECTORY_SEPARATOR.'fixtures');

		// grab elixir config
		$elixirFilename = base_path().DIRECTORY_SEPARATOR.'elixir.json';
		$elixirConfig   = file_exists($elixirFilename) ? json_decode(file_get_contents($elixirFilename), true) : [];

		// get the elixir buildDir setting
		$buildDir = trim(array_get($elixirConfig, 'buildDir'), DIRECTORY_SEPARATOR);
		$buildDir = empty($buildDir) ? '' : DIRECTORY_SEPARATOR.$buildDir;

		// run the file through the elixir helper
		$assetPath = elixir('test.css');

		// compare the results
		$this->assertEquals("{$buildDir}/build/test-123.css", $assetPath);
	}

}
