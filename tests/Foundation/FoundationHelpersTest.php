<?php

class FoundationHelpersTest extends PHPUnit_Framework_TestCase {

	public function testElixir()
	{
		// set fake public path to fixtures dir
		app()->instance('path.public', __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'public');

		// this should be set to a dir where it CANNOT find the elixir.json file
		app()->instance('path.base', __DIR__);

		// regular usage without considering elixir config
		$asset_path = elixir('test.css');
		$this->assertEquals('/build/test-123.css', $asset_path);
	}

	public function testElixirWithBuildPath()
	{
		// set fake public path to fixtures dir
		app()->instance('path.public', __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'public');

		// this should be set to a dir where it CAN find the elixir.json file
		app()->instance('path.base', __DIR__.DIRECTORY_SEPARATOR.'fixtures');

		// grab elixir config
		$elixir_filename = base_path().DIRECTORY_SEPARATOR.'elixir.json';
		$elixir_config   = file_exists($elixir_filename) ? json_decode(file_get_contents($elixir_filename), true) : [];

		// get the elixir buildDir setting
		$build_dir = trim(array_get($elixir_config, 'buildDir'), DIRECTORY_SEPARATOR);
		$build_dir = empty($build_dir) ? '' : DIRECTORY_SEPARATOR.$build_dir;

		// run the file through the elixir helper
		$asset_path = elixir('test.css');

		// compare the results
		$this->assertEquals("{$build_dir}/build/test-123.css", $asset_path);
	}

}
