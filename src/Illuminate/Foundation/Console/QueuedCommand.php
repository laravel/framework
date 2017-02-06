<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Console\Kernel as KernelContract;

class QueuedCommand implements ShouldQueue
{
    /**
     * The data to pass to the Artisan command.
     *
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Handle the job.
     *
     * @param  \Illuminate\Contracts\Console\Kernel  $kernel
     * @return void
     */
    public function handle(KernelContract $kernel)
    {
        call_user_func_array([$kernel, 'call'], $this->data);
    }
}
