<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class RetryMultipleCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:retry-multiple';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release multiple failed-jobs onto the queue.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        collect($this->argument('ids'))->each(function($id) {
            $this->call('queue:retry', ['id' => $id]);
        });
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [[
            'ids',
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'IDs of the failed-jobs you would like released (separate multiple ids with a space).'],
        ];
    }
}