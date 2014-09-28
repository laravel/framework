<?php namespace Illuminate\Filesystem\Adapters;

use Dropbox\Client;
use League\Flysystem\Adapter\Dropbox;

class DropboxConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Dropbox
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
		if ( ! array_key_exists('token', $config) || ! array_key_exists('app', $config))
		{
			throw new \InvalidArgumentException('The dropbox connector requires authentication.');
		}

		return array_only($config, ['token', 'app']);
	}

	/**
	 * Get the dropbox client.
	 *
	 * @param  array  $auth
	 * @return \Dropbox\Client
	 */
	protected function getClient(array $auth)
	{
		return new Client($auth['token'], $auth['app']);
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
	 * Get the dropbox adapter.
	 *
	 * @param  \Dropbox\Client  $client
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Dropbox
	 */
	protected function getAdapter(Client $client, array $config)
	{
		return new Dropbox($client, $config['prefix']);
	}

}
