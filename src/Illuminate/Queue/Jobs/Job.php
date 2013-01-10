<?php namespace Illuminate\Queue\Jobs;

abstract class Job {

	/**
	 * The job handler instance.
	 *
	 * @var mixed
	 */
	protected $instance;

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	abstract public function fire();

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	abstract public function delete();

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	abstract public function release($delay = 0);

	/**
	 * Determine if job should be auto-deleted.
	 *
	 * @return bool
	 */
	public function autoDelete()
	{
		return isset($this->instance->delete);
	}

}