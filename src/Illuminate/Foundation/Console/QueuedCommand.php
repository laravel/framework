<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class QueuedCommand implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

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
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }
        
        $kernel->call(...array_values($this->data));
    }
}
