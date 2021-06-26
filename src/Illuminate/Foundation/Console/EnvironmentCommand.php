<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;

class EnvironmentCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current app environment info';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $laravel = $this->getLaravel();

        $this->comment('Application Enviornment Details');

        $this->line('<info>Current application environment:</info> <comment>'.$laravel['env'].'</comment>');
        $this->line('<info>Laravel framework version:</info> <comment>'.Application::VERSION.'</comment>');
        $this->line('<info>Current php version:</info> <comment>'.PHP_VERSION.'</comment>');
        $this->line('<info>Application root:</info> <comment>'.$laravel['path.base'].'</comment>');
        $this->line('<info>Database connection:</info> <comment>'.$laravel['config']['database.default'].'</comment>');
    }
}
