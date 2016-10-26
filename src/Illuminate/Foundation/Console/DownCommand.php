<?php

namespace Illuminate\Foundation\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;

class DownCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'down {--message= : The message for the maintenance mode. }
            {--retry= : The number of seconds after which the request may be retried.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the application into maintenance mode';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        file_put_contents(
            $this->laravel->storagePath().'/framework/down',
            json_encode($this->getDownFilePayload(), JSON_PRETTY_PRINT)
        );

        $this->comment('Application is now in maintenance mode.');
    }

    /**
     * Get the payload to be placed in the "down" file.
     *
     * @return array
     */
    protected function getDownFilePayload()
    {
        return [
            'time' => Carbon::now()->getTimestamp(),
            'message' => $this->option('message'),
            'retry' => $this->getRetryTime(),
        ];
    }

    /**
     * Get the number of seconds the client should wait before retrying their request.
     *
     * @return int|null
     */
    protected function getRetryTime()
    {
        $retry = $this->option('retry');

        return is_numeric($retry) && $retry > 0 ? (int) $retry : null;
    }
}
