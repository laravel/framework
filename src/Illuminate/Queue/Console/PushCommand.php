<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;

class PushCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'queue:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push jobs from the secondary queue';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->laravel['queue.secondary']->all() as $job) {
            $this->retryJob($job);

            $this->info("Job [#{$job->id}] has been pushed back onto the queue!");

            $this->laravel['queue.secondary']->forget($job->id);
        }
    }

    /**
     * Retry the queue job.
     *
     * @param  \stdClass  $job
     * @return void
     */
    protected function retryJob($job)
    {
        $this->laravel['queue']->connection($job->connection)->push(
            unserialize($job->job), '', $job->queue
        );
    }
}
