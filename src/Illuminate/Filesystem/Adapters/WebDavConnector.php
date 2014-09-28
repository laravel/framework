<?php namespace Illuminate\Filesystem\Adapters;

use Sabre\DAV\Client;
use League\Flysystem\Adapter\WebDav;

class WebDavConnector implements ConnectorInterface {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\Adapter\WebDav
	 */
	public function connect(array $config)
	{
		$client = $this->getClient($config);

		return $this->getAdapter($client);
	}

	/**
	 * Get the webdav client.
	 *
	 * @param  array  $config
	 * @return \Sabre\DAV\Client
	 */
	protected function getClient(array $config)
	{
		return new Client($config);
	}

	/**
	 * Get the webdav adapter.
	 *
	 * @param  \Sabre\DAV\Client  $client
	 * @return \League\Flysystem\Adapter\WebDav
	 */
	protected function getAdapter(Client $client)
	{
		return new WebDav($client);
	}

}
