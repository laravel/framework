<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class FooCommand extends Command
{
    protected $signature = 'foo:command';

    protected $description = 'This is the description of the command.';
}
