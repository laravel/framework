<?php namespace Illuminate\Foundation\Console;

use Illuminate\Contracts\Console\Kernel;

class QueuedJob {

	/**
	 * The kernel instance.
	 *
	 * @var \Illuminate\Contracts\Console\Kernel
	 */
	protected $kernel;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Contracts\Console\Kernel  $kernel
	 * @return void
	 */
	public function __construct(Kernel $kernel)
	{
		$this->kernel = $kernel;
	}

	/**
	 * Fire the job.
	 *
	 * @param  \Illuminate\Queue\Jobs\Job
	 * @param  array  $data
	 * @return void
	 */
	public function fire($job, $data)
	{
		$status = call_user_func_array([$this->kernel, 'call'], $data);

		$job->delete();
	}

}
