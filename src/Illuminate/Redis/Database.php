<?php namespace Illuminate\Redis;

use Predis\Client;
use Illuminate\Contracts\Redis\Database as DatabaseContract;

class Database implements DatabaseContract {

	/**
	 * The host address of the database.
	 *
	 * @var array
	 */
	protected $clients;

	/**
	 * Create a new Redis connection instance.
	 *
	 * @param  array  $servers
	 * @return void
	 */
	public function __construct(array $servers = array())
	{
		$cluster = array_pull($servers, 'cluster');
		$options = (array)array_pull($servers, 'options');

		if ($cluster)
		{
			$this->clients = $this->createAggregateClient($servers, $options);
		}
		else
		{
			$this->clients = $this->createSingleClients($servers, $options);
		}
	}

	/**
	 * Create a new aggregate client supporting sharding.
	 *
	 * @param  array  $servers
	 * @param  array  $options
	 * @return array
	 */
	protected function createAggregateClient(array $servers, $options = [])
	{
		return array('default' => new Client(array_values($servers), $options));
	}

	/**
	 * Create an array of single connection clients.
	 *
	 * @param  array  $servers
	 * @param  array  $options
	 * @return array
	 */
	protected function createSingleClients(array $servers, $options = [])
	{
		$clients = array();

		foreach ($servers as $key => $server)
		{
			$clients[$key] = new Client($server, $options);
		}

		return $clients;
	}

	/**
	 * Get a specific Redis connection instance.
	 *
	 * @param  string  $name
	 * @return \Predis\ClientInterface|null
	 */
	public function connection($name = 'default')
	{
		return array_get($this->clients, $name ?: 'default');
	}

	/**
	 * Run a command against the Redis database.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function command($method, array $parameters = array())
	{
		return call_user_func_array(array($this->clients['default'], $method), $parameters);
	}

	/**
	 * Dynamically make a Redis command.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->command($method, $parameters);
	}

}
