<?php namespace Illuminate\Filesystem\Adapters;

use OpenCloud\OpenStack;
use League\Flysystem\Adapter\Rackspace;
use OpenCloud\ObjectStore\Resource\Container;

class RackspaceConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\Rackspace
	 */
	public function connect(array $config)
	{
		$auth = $this->getAuth($config);
		$client = $this->getClient($auth);

		return $this->getAdapter($client);
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
		if ( ! array_key_exists('username', $config) || ! array_key_exists('password', $config))
		{
			throw new \InvalidArgumentException('The rackspace connector requires authentication.');
		}

		if ( ! array_key_exists('endpoint', $config) || ! array_key_exists('container', $config))
		{
			throw new \InvalidArgumentException('The rackspace connector requires configuration.');
		}

		return array_only($config, ['username', 'password', 'endpoint', 'container']);
	}

	/**
	 * Get the rackspace client.
	 *
	 * @param  array  $auth
	 * @return \OpenCloud\ObjectStore\Resource\Container
	 */
	protected function getClient(array $auth)
	{
		$client = new OpenStack($auth['endpoint'], [
			'username' => $auth['username'],
			'password' => $auth['password'],
		]);

		return $client->objectStoreService('cloudFiles', 'LON')->getContainer($auth['container']);
	}

	/**
	 * Get the rackspace adapter.
	 *
	 * @param  \OpenCloud\ObjectStore\Resource\Container  $client
	 * @return \League\Flysystem\Adapter\Rackspace
	 */
	protected function getAdapter(Container $client)
	{
		return new Rackspace($client);
	}

}
