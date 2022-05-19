<?php

namespace Illuminate\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProtectedCommand extends Command
{
    /**
     * Create a new console command instance.
     * Not used for protected command.
     *
     * @return void
     */
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * Not used for protected command.
     *
     * @return int
     */
    final public function handle()
    {
        return 0;
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (method_exists($this, '__invoke')) {
            return (int) $this->laravel->call([$this, '__invoke']);
        }

        return 1;
    }
}
