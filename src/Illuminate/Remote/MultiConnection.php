<?php namespace Illuminate\Remote;

use Closure;

class MultiConnection implements ConnectionInterface {

	/**
	 * All of the active server connections.
	 *
	 * @var array
	 */
	protected $connections;

	/**
	 * The array of connections.
	 *
	 * @param  array  $connections
	 * @return void
	 */
	public function __construct(array $connections)
	{
		$this->connections = $connections;
	}

	/**
	 * Run a set of commands against the connection.
	 *
	 * @param  string|array  $commands
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function run($commands, Closure $callback = null)
	{
		foreach ($this->connections as $connection)
		{
			$connection->run($commands, $callback);
		}
	}

	/**
	 * Upload a local file to the server.
	 *
	 * @param  string  $local
	 * @param  string  $remote
	 * @return void
	 */
	public function put($local, $remote)
	{
		foreach ($this->connections as $connection)
		{
			$connection->put($local, $remote);
		}
	}

	/**
	 * Upload a string to to the given file on the server.
	 *
	 * @param  string  $remote
	 * @param  string  $contents
	 * @return void
	 */
	public function putString($remote, $contents)
	{
		foreach ($this->connections as $connection)
		{
			$connection->putString($remote, $contents);
		}
	}

}