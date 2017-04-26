<?php

namespace Illuminate\Queue\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Queue\Jobs\TestJob;

class TestCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:test {message?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a message to the queue';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $message = $this->argument('message');
        if (! $message) {
            $message = 'Queue test message - '.Carbon::now()->toDateTimeString();
        }

        dispatch(new TestJob($message));
        $this->info('Message added to queue');
    }
}
