<?php

namespace Illuminate\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProtectedCommand
 * @package Illuminate\Console
 */
class ProtectedCommand extends Command
{
    /**
     * Not used for protected command
     */
    final public function __construct()
    {
        parent::__construct();
    }

    /**
     * Not used for protected command
     */
    final public function handle()
    {
        // Not implement handle() method.
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
