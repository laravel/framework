<?php namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\DatabaseQueue;
use Illuminate\Database\ConnectionResolverInterface;

class DatabaseConnector implements ConnectorInterface {

	/**
	 * Create a new connector instance.
	 *
	 * @param  \Illuminate\Database\ConnectionResolverInterface  $connections
	 * @return void
	 */
	public function __construct(ConnectionResolverInterface $connections)
	{
		$this->connections = $connections;
	}

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function connect(array $config)
	{
		return new DatabaseQueue(
			$this->connections->connection(array_get($config, 'connection')),
			$config['table'],
			$config['queue'],
			array_get($config, 'expire', 60)
		);
	}

}
