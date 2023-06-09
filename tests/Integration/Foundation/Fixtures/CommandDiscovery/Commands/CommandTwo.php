<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands;

use Illuminate\Console\Command;

class CommandTwo extends Command
{
    protected $signature = 'command-two';

    public function handle()
    {
        return self::SUCCESS;
    }
}
