<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('command-name', aliases: ['command-alias'])]
class CommandWithAliasViaAttribute extends Command
{
    //
}
