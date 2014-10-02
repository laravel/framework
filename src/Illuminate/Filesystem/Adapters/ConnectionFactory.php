<?php namespace Illuminate\Filesystem\Adapters;

class ConnectionFactory {

	/**
	 * Establish an adapter connection.
	 *
	 * @param  array  $config
	 * @return \League\Flysystem\AdapterInterface
	 */
	public function make(array $config)
	{
		return $this->createConnector($config)->connect($config);
	}

	/**
	 * Create a connector instance based on the configuration.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Filesystem\Adapters\ConnectorInterface
	 *
	 * @throws \InvalidArgumentException
	 */
	public function createConnector(array $config)
	{
		if ( ! isset($config['driver']))
		{
			throw new \InvalidArgumentException("A driver must be specified.");
		}

		switch ($config['driver'])
		{
			case 'awss3':
				return new AwsS3Connector();
			case 'local':
				return new LocalConnector();
			case 'null':
				return new NullConnector();
			case 'rackspace':
				return new RackspaceConnector();
		}

		throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}]");
	}

}
