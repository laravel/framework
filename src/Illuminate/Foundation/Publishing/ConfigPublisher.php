<?php namespace Illuminate\Foundation\Publishing;

class ConfigPublisher extends Publisher {

	/**
	 * Get the source configuration directory to publish.
	 *
	 * @param  string  $package
	 * @param  string  $packagePath
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getSource($package, $packagePath)
	{
		$source = $packagePath."/{$package}/src/config";

		if ( ! $this->files->isDirectory($source))
		{
			throw new \InvalidArgumentException("Configuration not found.");
		}

		return $source;
	}

}
