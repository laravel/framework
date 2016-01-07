<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class DownCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'down {--message? : The message for the maintenance mode. }
            {--retry? : The number of seconds after which the request may be retried.}';

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
        $data = [
            'time' => time(),
            'message' => $this->argument('message'),
        ];

        $retry = $this->argument('retry');
        $data['retry'] = is_numeric($retry) && $retry > 0 ? (int) $retry : null;

        file_put_contents($this->laravel->storagePath().'/framework/down', json_encode($data, JSON_PRETTY_PRINT));

        $this->comment('Application is now in maintenance mode.');
    }
}
