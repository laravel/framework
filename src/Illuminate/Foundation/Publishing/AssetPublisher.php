<?php namespace Illuminate\Foundation\Publishing;

class AssetPublisher extends Publisher {

	/**
	 * Get the source assets directory to publish.
	 *
	 * @param  string  $package
	 * @param  string  $packagePath
	 * @return string
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getSource($package, $packagePath)
	{
		$source = $packagePath."/{$package}/".$this->registrar->getAssetsPath($package);

		if ( ! $this->files->isDirectory($source))
		{
			throw new \InvalidArgumentException("Assets not found.");
		}

		return $source;
	}

}
