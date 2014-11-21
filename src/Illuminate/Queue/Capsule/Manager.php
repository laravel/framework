<?php namespace Illuminate\Queue\Capsule;

use Illuminate\Queue\QueueManager;
use Illuminate\Container\Container;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Support\Traits\CapsuleManagerTrait;

class Manager {

	use CapsuleManagerTrait;

	/**
	 * The queue manager instance.
	 *
	 * @var \Illuminate\Queue\QueueManager
	 */
	protected $manager;

	/**
	 * Create a new queue capsule manager.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container = null)
	{
		$this->setupContainer($container ?: new Container);

		// Once we have the container setup, we will setup the default configuration
		// options in the container "config" bindings. This just makes this queue
		// manager behave correctly since all the correct binding are in place.
		$this->setupDefaultConfiguration();

		$this->setupManager();

		$this->registerConnectors();
	}

	/**
	 * Setup the default queue configuration options.
	 *
	 * @return void
	 */
	protected function setupDefaultConfiguration()
	{
		$this->container['config']['queue.default'] = 'default';
	}

	/**
	 * Build the queue manager instance.
	 *
	 * @return void
	 */
	protected function setupManager()
	{
		$this->manager = new QueueManager($this->container);
	}

	/**
	 * Register the default connectors that the component ships with.
	 *
	 * @return void
	 */
	protected function registerConnectors()
	{
		$provider = new QueueServiceProvider($this->container);

		$provider->registerConnectors($this->manager);
	}

	/**
	 * Get a connection instance from the global manager.
	 *
	 * @param  string  $connection
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public static function connection($connection = null)
	{
		return static::$instance->getConnection($connection);
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @param  string  $connection
	 * @return mixed
	 */
	public static function push($job, $data = '', $queue = null, $connection = null)
	{
		return static::$instance->connection($connection)->push($job, $data, $queue);
	}

	/**
	 * Push a new an array of jobs onto the queue.
	 *
	 * @param  array   $jobs
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @param  string  $connection
	 * @return mixed
	 */
	public static function bulk($jobs, $data = '', $queue = null, $connection = null)
	{
		return static::$instance->connection($connection)->bulk($jobs, $data, $queue);
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @param  string  $connection
	 * @return mixed
	 */
	public static function later($delay, $job, $data = '', $queue = null, $connection = null)
	{
		return static::$instance->connection($connection)->later($delay, $job, $data, $queue);
	}

	/**
	 * Get a registered connection instance.
	 *
	 * @param  string  $name
	 * @return \Illuminate\Contracts\Queue\Queue
	 */
	public function getConnection($name = null)
	{
		return $this->manager->connection($name);
	}

	/**
	 * Register a connection with the manager.
	 *
	 * @param  array   $config
	 * @param  string  $name
	 * @return void
	 */
	public function addConnection(array $config, $name = 'default')
	{
		$this->container['config']["queue.connections.{$name}"] = $config;
	}

	/**
	 * Get the queue manager instance.
	 *
	 * @return \Illuminate\Queue\QueueManager
	 */
	public function getQueueManager()
	{
		return $this->manager;
	}

	/**
	 * Pass dynamic instance methods to the manager.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->manager, $method), $parameters);
	}

	/**
	 * Dynamically pass methods to the default connection.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		return call_user_func_array(array(static::connection(), $method), $parameters);
	}

}
