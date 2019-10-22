<?php

namespace Illuminate\Auth\Console;

use InvalidArgumentException;
use Illuminate\Console\Command;

class AuthMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:auth {scaffold=vue}
                            {--auth : Only scaffold the authentication views}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install laravel/ui package and scaffold basic login and registration views and routes';

    /**
     * Execute the console command.
     *
     * @return mixed
     * 
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        if (! in_array($this->argument('scaffold'), ['bootstrap', 'vue', 'react'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->info('Running this command will install laravel/ui package.');
        $this->confirm('Would you like to proceed?');
        $this->callSilent('composer require laravel/ui --dev');

        if ($this->option('auth')){
            $this->callSilent('php artisan ui '.$this->argument('scaffold').' --auth');
            $this->info('Authentication and '.ucfirst($this->argument('scaffold')).' scaffolding successfully installed.');
        }
        else {
            $this->callSilent('php artisan ui '.$this->argument('scaffold'));
            $this->info(ucfirst($this->argument('scaffold')).' scaffolding successfully installed.'); 
        }
    }
}
