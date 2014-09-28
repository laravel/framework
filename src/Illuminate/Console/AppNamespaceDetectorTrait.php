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
			$appPath = explode('/', app_path());
			$appFolderName = array_pop($appPath);
			if ($path == $appFolderName . '/') return $namespace;
		}

		throw new \RuntimeException("Unable to detect application namespace.");
	}

}
