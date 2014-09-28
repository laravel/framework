<?php namespace Illuminate\Filesystem\Adapters;

use Barracuda\Copy\API;
use League\Flysystem\Adapter\Copy;

class CopyConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Copy
	 */
	public function connect(array $config)
	{
		$auth = $this->getAuth($config);
		$client = $this->getClient($auth);
		$config = $this->getConfig($config);

		return $this->getAdapter($client, $config);
	}

	/**
	 * Get the authentication data.
	 *
	 * @param  array  $config
	 * @return array
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function getAuth(array $config)
	{
		if ( ! array_key_exists('consumer-key', $config) || ! array_key_exists('consumer-secret', $config))
		{
			throw new \InvalidArgumentException('The copy connector requires consumer configuration.');
		}

		if ( ! array_key_exists('access-token', $config) || ! array_key_exists('token-secret', $config))
		{
			throw new \InvalidArgumentException('The copy connector requires authentication.');
		}

		return array_only($config, ['consumer-key', 'consumer-secret', 'access-token', 'token-secret']);
	}

	/**
	 * Get the copy client.
	 *
	 * @param  array  $auth
	 * @return \Barracuda\Copy\API
	 */
	protected function getClient(array $auth)
	{
		return new API($auth['consumer-key'], $auth['consumer-secret'], $auth['access-token'], $auth['token-secret']);
	}

	/**
	 * Get the configuration.
	 *
	 * @param  array  $config
	 * @return array
	 */
	protected function getConfig(array $config)
	{
		if ( ! array_key_exists('prefix', $config))
		{
			$config['prefix'] = null;
		}

		return array_only($config, ['prefix']);
	}

	/**
	 * Get the copy adapter.
	 *
	 * @param  \Barracuda\Copy\API  $client
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Copy
	 */
	protected function getAdapter(API $client, array $config)
	{
		return new Copy($client, $config['prefix']);
	}

}
