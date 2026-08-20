<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class BarCommandStub extends Command
{
    protected $signature = 'bar:command';

    protected $description = 'This is the description of the command.';
}
