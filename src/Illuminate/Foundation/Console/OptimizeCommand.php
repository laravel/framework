<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class OptimizeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the framework for better performance (deprecated)';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the compiled class file to be written (deprecated).'],

            ['psr', null, InputOption::VALUE_NONE, 'Do not optimize Composer dump-autoload.'],
        ];
    }
}
