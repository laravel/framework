<?php

namespace Illuminate\Console\Jobs;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class DispatchCommand extends Command
{
    use DispatchesJobs;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches a job';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'job:dispatch {job : The name of the job that should be dispatched}';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $job = $this->argument('job');

        $this->info("Dispatching {$job} now...");

        $this->dispatch(new $job());

        $this->info('Successfully Dispatched Job');
    }
}
