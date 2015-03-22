<?php namespace Illuminate\Console;

trait AppNamespaceDetectorTrait {

	/**
	 * Get the application namespace from the Kernel object.
	 *
	 * @return string
	 *
	 * @throws \RuntimeException
	 */
	protected function getAppNamespace()
	{
		$kernelContract = app()->runningInConsole()
							? 'Illuminate\Contracts\Console\Kernel'
							: 'Illuminate\Contracts\Http\Kernel';

		return strtok(get_class(app($kernelContract)), '\\').'\\';
	}

}
