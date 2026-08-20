<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foo:bar')]
#[Help('Extended help text.')]
class HelpCommand extends Command
{
    public function handle()
    {
    }
}
