<?php namespace Illuminate\Filesystem\Adapters;

use League\Flysystem\Adapter\Zip;

class ZipConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Zip
	 */
	public function connect(array $config)
	{
		$config = $this->getConfig($config);

		return $this->getAdapter($config);
	}

	/**
	 * Get the configuration.
	 *
	 * @param  array  $config
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getConfig(array $config)
	{
		if ( ! array_key_exists('path', $config))
		{
			throw new \InvalidArgumentException('The zip connector requires a path.');
		}

		return array_only($config, ['path']);
	}

	/**
	 * Get the zip adapter.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Zip
	 */
	protected function getAdapter(array $config)
	{
		return new Zip($config['path']);
	}

}
