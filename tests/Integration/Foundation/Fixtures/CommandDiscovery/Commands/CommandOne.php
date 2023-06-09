<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\CommandDiscovery\Commands;

use Illuminate\Console\Command;

class CommandOne extends Command
{
    protected $signature = 'command-one';

    public function handle()
    {
        return self::SUCCESS;
    }
}
