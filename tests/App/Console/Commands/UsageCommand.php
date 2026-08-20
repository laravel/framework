<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;

#[Signature('foo:bar {user}')]
#[Usage('foo:bar 1')]
#[Usage('foo:bar 1 --force')]
class UsageCommand extends Command
{
    public function handle()
    {
    }
}
