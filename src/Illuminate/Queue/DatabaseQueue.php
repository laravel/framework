<?php namespace Illuminate\Queue;

use DateTime;
use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Database\Query\Expression;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class DatabaseQueue extends Queue implements QueueContract {

	/**
	* The database connection instance.
	*
	 * @var \Illuminate\Database\Connection
	 */
	protected $database;

	/**
	 * The database table that holds the jobs.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The name of the default queue.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * The expiration time of a job.
	 *
	 * @var int|null
	 */
	protected $expire = 60;

	/**
	 * Create a new database queue instance.
	 *
	 * @param  \Illuminate\Database\Connection  $database
	 * @param  string  $table
	 * @param  string  $default
	 * @param  int  $expire
	 * @return void
	 */
	public function __construct(Connection $database, $table, $default = 'default', $expire = 60)
	{
		$this->table = $table;
		$this->expire = $expire;
		$this->default = $default;
		$this->database = $database;
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
		return $this->pushToDatabase(0, $queue, $this->createPayload($job, $data));
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
		return $this->pushToDatabase(0, $queue, $payload);
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
		return $this->pushToDatabase($delay, $queue, $this->createPayload($job, $data));
	}

	/**
	 * Release a reserved job back onto the queue.
	 *
	 * @param  string  $queue
	 * @param  \StdClass  $job
	 * @param  int  $delay
	 * @return void
	 */
	public function release($queue, $job, $delay)
	{
		return $this->pushToDatabase($delay, $queue, $job->payload, $job->attempts);
	}

	/**
	 * Push a raw payload to the database with a given delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string|null  $queue
	 * @param  string  $payload
	 * @param  int  $attempts
	 * @return mixed
	 */
	protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
	{
		$availableAt = $delay instanceof DateTime ? $delay : Carbon::now()->addSeconds($delay);

		return $this->database->table($this->table)->insertGetId([
			'queue' => $this->getQueue($queue),
			'payload' => $payload,
			'attempts' => $attempts,
			'reserved' => 0,
			'reserved_at' => null,
			'available_at' => $availableAt->getTimestamp(),
			'created_at' => $this->getTime(),
		]);
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Contracts\Queue\Job|null
	 */
	public function pop($queue = null)
	{
		$queue = $this->getQueue($queue);

		if ( ! is_null($this->expire))
		{
			$this->releaseJobsThatHaveBeenReservedTooLong($queue);
		}

		if ($job = $this->getNextAvailableJob($queue))
		{
			$this->markJobAsReserved($job->id);

			$this->database->commit();

			return new DatabaseJob(
				$this->container, $this, $job, $queue
			);
		}

		$this->database->commit();
	}

	/**
	 * Release the jobs that have been reserved for too long.
	 *
	 * @param  string  $queue
	 * @return void
	 */
	protected function releaseJobsThatHaveBeenReservedTooLong($queue)
	{
		$expired = Carbon::now()->subSeconds($this->expire)->getTimestamp();

		$this->database->table($this->table)
					->where('queue', $this->getQueue($queue))
					->where('reserved', 1)
					->where('reserved_at', '<=', $expired)
					->update([
						'reserved' => 0,
						'reserved_at' => null,
						'attempts' => new Expression('attempts + 1'),
					]);
	}

	/**
	 * Get the next available job for the queue.
	 *
	 * @param  string|null  $queue
	 * @return \StdClass|null
	 */
	protected function getNextAvailableJob($queue)
	{
		$this->database->beginTransaction();

		$job = $this->database->table($this->table)
					->lockForUpdate()
					->where('queue', $this->getQueue($queue))
					->where('reserved', 0)
					->where('available_at', '<=', $this->getTime())
					->orderBy('id', 'asc')
					->first();

		return $job ? (object) $job : null;
	}

	/**
	 * Mark the given job ID as reserved.
	 *
	 * @param  string  $id
	 * @return void
	 */
	protected function markJobAsReserved($id)
	{
		$this->database->table($this->table)->where('id', $id)->update([
			'reserved' => 1, 'reserved_at' => $this->getTime(),
		]);
	}

	/**
	 * Delete a reserved job from the queue.
	 *
	 * @param  string  $queue
	 * @param  string  $id
	 * @return void
	 */
	public function deleteReserved($queue, $id)
	{
		$this->database->table($this->table)->where('id', $id)->delete();
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	protected function getQueue($queue)
	{
		return $queue ?: $this->default;
	}

	/**
	 * Get the underlying database instance.
	 *
	 * @return \Illuminate\Database\Connection
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * Get the expiration time in seconds.
	 *
	 * @return int|null
	 */
	public function getExpire()
	{
		return $this->expire;
	}

	/**
	 * Set the expiration time in seconds.
	 *
	 * @param  int|null  $seconds
	 * @return void
	 */
	public function setExpire($seconds)
	{
		$this->expire = $seconds;
	}

}
