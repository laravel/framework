<?php namespace Illuminate\Queue\Connectors;

use Illuminate\Queue\DatabaseQueue;
use Illuminate\Contracts\Queue\Connector as ConnectorContract;
use Illuminate\Contracts\Database\ConnectionResolver as ConnectionResolverContract;

class DatabaseConnector implements ConnectorContract {

	/**
	 * Database connections.
	 *
	 * @var \Illuminate\Database\ConnectionResolverInterface
	 */
	protected $connections;

	/**
	 * Create a new connector instance.
	 *
	 * @param  \Illuminate\Contracts\Database\ConnectionResolver  $connections
	 * @return void
	 */
	public function __construct(ConnectionResolverContract $connections)
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
