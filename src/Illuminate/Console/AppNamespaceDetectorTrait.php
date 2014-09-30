<?php namespace Illuminate\Console;

trait AppNamespaceDetectorTrait {

	/**
	 * Get the application namespace from the Composer file.
	 *
	 * @param  string  $namespacePath
	 * @return string
	 */
	protected function getAppNamespace($namespacePath = 'app')
	{
		$composer = (array) json_decode(file_get_contents(base_path().'/composer.json', true));

		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path)
		{
			if ($path == "{$namespacePath}/") return $namespace;
		}

		throw new \RuntimeException("Unable to detect application namespace.");
	}

}
