<?php

namespace Illuminate\Auth\Console;

use Illuminate\Console\Command;

class ClearResetsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'auth:clear-resets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush expired password reset tokens';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel['auth.password.tokens']->deleteExpired();

        $this->info('Expired reset tokens cleared!');
    }
}
