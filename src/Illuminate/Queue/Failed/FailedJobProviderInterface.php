<?php namespace Illuminate\Queue\Failed;

use Exception;

interface FailedJobProviderInterface {

	/**
	 * Log a failed job into storage.
	 *
	 * @param  string  $connection
	 * @param  string  $queue
	 * @param  string  $payload
	 * @param  \Exception|null  $exception
	 * @return void
	 */
	public function log($connection, $queue, $payload, Exception $exception = null);

	/**
	 * Get a list of all of the failed jobs.
	 *
	 * @return array
	 */
	public function all();

	/**
	 * Get a single failed job.
	 *
	 * @param  mixed  $id
	 * @return array
	 */
	public function find($id);

	/**
	 * Delete a single failed job from storage.
	 *
	 * @param  mixed  $id
	 * @return bool
	 */
	public function forget($id);

	/**
	 * Flush all of the failed jobs from storage.
	 *
	 * @return void
	 */
	public function flush();

}
