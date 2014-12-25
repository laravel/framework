<?php namespace Illuminate\Queue;

use Illuminate\Contracts\Queue\Job as JobContract;

trait InteractsWithQueue {

	/**
	 * The underlying queue job instance.
	 *
	 * @var \Illuminate\Contracts\Queue\Jobs
	 */
	protected $job;

	/**
	 * Delete the job from the queue.
	 *
	 * @return void
	 */
	public function delete()
	{
		return $this->job->delete();
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		return $this->job->release($delay);
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return $this->job->attempts();
	}

	/**
	 * Set the base queue job instance.
	 *
	 * @param  \Illuminate\Contracts\Queue\Job
	 * @return $this
	 */
	public function setJob(JobContract $job)
	{
		$this->job = $job;

		return $this;
	}

}
