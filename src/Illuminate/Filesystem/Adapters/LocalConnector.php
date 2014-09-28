<?php namespace Illuminate\Filesystem\Adapters;

use League\Flysystem\Adapter\Local;

class LocalConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Local
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
			throw new \InvalidArgumentException('The local connector requires a path.');
		}

		return array_only($config, ['path']);
	}

	/**
	 * Get the local adapter.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Local
	 */
	protected function getAdapter(array $config)
	{
		return new Local($config['path']);
	}

}
