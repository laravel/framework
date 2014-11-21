<?php namespace Illuminate\Foundation\Publishing;

class ViewPublisher extends Publisher {

	/**
	 * Get the source views directory to publish.
	 *
	 * @param  string  $package
	 * @param  string  $packagePath
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getSource($package, $packagePath)
	{
		$source = $packagePath."/{$package}/".$this->registrar->getViewsPath($package);

		if ( ! $this->files->isDirectory($source))
		{
			throw new \InvalidArgumentException("Views not found.");
		}

		return $source;
	}

}
