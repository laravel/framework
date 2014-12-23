<?php namespace Illuminate\Console;

use RuntimeException;

trait AppNamespaceDetectorTrait {

	/**
	 * Get the application namespace from the Composer file.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	protected function getAppNamespace()
	{
		$composer = json_decode(file_get_contents(base_path().'/composer.json'), true);

		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path)
		{
			foreach ((array) $path as $pathChoice)
			{
				if (realpath(app_path()) == realpath(base_path().'/'.$pathChoice)) return $namespace;
			}
		}

		throw new RuntimeException("Unable to detect application namespace.");
	}

}
