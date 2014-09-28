<?php namespace Illuminate\Console;

trait AppNamespaceDetectorTrait {

	/**
	 * Get the application namespace from the Composer file.
	 *
	 * @return string
	 */
	protected function getAppNamespace()
	{
		$composer = (array) json_decode(file_get_contents(base_path().'/composer.json', true));

		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path)
		{
			$app_path = explode('/', app_path());
			$app_folder_name = array_pop($app_path);
			if ($path == $app_folder_name . '/') return $namespace;
		}

		throw new \RuntimeException("Unable to detect application namespace.");
	}

}
