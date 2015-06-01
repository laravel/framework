<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Contracts\Console\Kernel as KernelContract;

class QueuedJob
{
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
    public function __construct(KernelContract $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Fire the job.
     *
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  array  $data
     * @return void
     */
    public function fire($job, $data)
    {
        call_user_func_array([$this->kernel, 'call'], $data);

        $job->delete();
    }
}
