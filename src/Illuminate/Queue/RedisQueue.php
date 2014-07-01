<?php namespace Illuminate\Queue;

use Illuminate\Redis\Database;
use Illuminate\Queue\Jobs\RedisJob;

class RedisQueue extends Queue implements QueueInterface {

	/**
	* The Redis database instance.
	*
	 * @var \Illuminate\Redis\Database
	 */
	protected $redis;

	/**
	 * The connection name.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The name of the default queue.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new Redis queue instance.
	 *
	 * @param  \Illuminate\Redis\Database  $redis
	 * @param  string  $default
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct(Database $redis, $default = 'default', $connection = null)
	{
		$this->redis = $redis;
		$this->default = $default;
		$this->connection = $connection;
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function push($job, $data = '', $queue = null)
	{
		return $this->pushRaw($this->createPayload($job, $data), $queue);
	}

	/**
	 * Push a raw payload onto the queue.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  array   $options
	 * @return mixed
	 */
	public function pushRaw($payload, $queue = null, array $options = array())
	{
		$this->redis->rpush($this->getQueue($queue), $payload);

		return array_get(json_decode($payload, true), 'id');
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return void
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		$payload = $this->createPayload($job, $data);

		$delay = $this->getSeconds($delay);

		$this->redis->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);

		return array_get(json_decode($payload, true), 'id');
	}

	/**
	 * Release a reserved job back onto the queue.
	 *
	 * @param  string  $queue
	 * @param  string  $payload
	 * @param  int  $delay
	 * @param  int  $attempts
	 * @return void
	 */
	public function release($queue, $payload, $delay, $attempts)
	{
		$payload = $this->setMeta($payload, 'attempts', $attempts);

		$this->redis->zadd($this->getQueue($queue).':delayed', $this->getTime() + $delay, $payload);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$original = $queue ?: $this->default;

		$this->migrateAllExpiredJobs($queue = $this->getQueue($queue));

		$job = $this->redis->lpop($queue);

		if ( ! is_null($job))
		{
			$this->redis->zadd($queue.':reserved', $this->getTime() + 60, $job);

			return new RedisJob($this->container, $this, $job, $original);
		}
	}

	/**
	 * Delete a reserved job from the queue.
	 *
	 * @param  string  $queue
	 * @param  string  $job
	 * @return void
	 */
	public function deleteReserved($queue, $job)
	{
		$this->redis->zrem($this->getQueue($queue).':reserved', $job);
	}

	/**
	 * Migrate all of the waiting jobs in the queue.
	 *
	 * @param  string  $queue
	 * @return void
	 */
	protected function migrateAllExpiredJobs($queue)
	{
		$this->migrateExpiredJobs($queue.':delayed', $queue);

		$this->migrateExpiredJobs($queue.':reserved', $queue);
	}

	/**
	 * Migrate the delayed jobs that are ready to the regular queue.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	public function migrateExpiredJobs($from, $to)
	{
		$options = array('cas' => true, 'watch' => $from, 'retry' => 10);

		$this->redis->transaction($options, function ($tx) use ($from, $to) {

			$time = $this->getTime();

			@list($jobs) = $tx->zrangebyscore($from, '-inf', $time);

			if (isset($jobs) && count($jobs) > 0)
			{
				$tx->multi();
				$tx->zremrangebyscore($from, '-inf', $time);
			}

			call_user_func_array(array($tx, 'rpush'), array_merge(array($to), $jobs));

		});
	}

	/**
	 * Create a payload string from the given job and data.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return string
	 */
	protected function createPayload($job, $data = '', $queue = null)
	{
		$payload = parent::createPayload($job, $data);

		$payload = $this->setMeta($payload, 'id', $this->getRandomId());

		return $this->setMeta($payload, 'attempts', 1);
	}

	/**
	 * Get a random ID string.
	 *
	 * @return string
	 */
	protected function getRandomId()
	{
		return str_random(32);
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	protected function getQueue($queue)
	{
		return 'queues:'.($queue ?: $this->default);
	}

	/**
	 * Get the underlying Redis instance.
	 *
	 * @return \Illuminate\Redis\Database
	 */
	public function getRedis()
	{
		return $this->redis;
	}

}
