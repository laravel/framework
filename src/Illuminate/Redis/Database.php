<?php namespace Illuminate\Redis;

use Closure;
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
		if (isset($servers['cluster']) && $servers['cluster'])
		{
			$this->clients = $this->createAggregateClient($servers);
		}
		else
		{
			$this->clients = $this->createSingleClients($servers);
		}
	}

	/**
	 * Create a new aggregate client supporting sharding.
	 *
	 * @param  array  $servers
	 * @return array
	 */
	protected function createAggregateClient(array $servers)
	{
		$servers = array_except($servers, array('cluster'));

		$options = $this->getClientOptions($servers);

		return array('default' => new Client(array_values($servers), $options));
	}

	/**
	 * Create an array of single connection clients.
	 *
	 * @param  array  $servers
	 * @return array
	 */
	protected function createSingleClients(array $servers)
	{
		$clients = array();

		$options = $this->getClientOptions($servers);

		foreach ($servers as $key => $server)
		{
			$clients[$key] = new Client($server, $options);
		}

		return $clients;
	}

	/**
	 * Get any client options from the configuration array.
	 *
	 * @param  array  $servers
	 * @return array
	 */
	protected function getClientOptions(array $servers)
	{
		return isset($servers['options']) ? (array) $servers['options'] : [];
	}

	/**
	 * Get a specific Redis connection instance.
	 *
	 * @param  string  $name
	 * @return \Predis\ClientInterface
	 */
	public function connection($name = 'default')
	{
		return $this->clients[$name ?: 'default'];
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
	 * Subscribe to a set of given channels for messages.
	 *
	 * @param  array|string  $channels
	 * @param  \Closure  $callback
	 * @param  string  $connection
	 * @return void
	 */
	public function subscribe($channels, Closure $callback, $connection = null)
	{
		$loop = $this->connection($connection)->pubSubLoop();

		call_user_func_array([$loop, 'subscribe'], (array) $channels);

		foreach ($loop as $message) {
			if ($message->kind === 'message') {
				call_user_func($callback, $message->payload, $message->channel);
			}
		}

		unset($loop);
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
