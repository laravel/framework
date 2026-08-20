<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class CommandWithNoAliasViaProperty extends Command
{
    public $name = 'command-name';
}
