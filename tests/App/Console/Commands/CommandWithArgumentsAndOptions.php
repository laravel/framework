<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class CommandWithArgumentsAndOptions extends Command
{
    protected $signature = 'command-events-test-command {firstname} {lastname} {--occupation=cook}';

    public function handle()
    {
        // ...
    }
}
