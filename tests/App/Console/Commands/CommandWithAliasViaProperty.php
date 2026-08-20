<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class CommandWithAliasViaProperty extends Command
{
    public $name = 'command-name';
    public $aliases = ['command-alias'];
}
