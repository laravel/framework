<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class RetryAllCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:retry-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release all failed-jobs onto the queue.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $failed = $this->laravel['queue.failer']->all();

        if (! empty($failed)) {
            collect($failed)->each(function ($value) {
                $this->call('queue:retry', ['id' => $value->id]);
            });
        } else {
            $this->error('No failed jobs.');
        }
    }
}
