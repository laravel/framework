<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foo:bar')]
#[Hidden]
class HiddenCommand extends Command
{
    public function handle()
    {
    }
}
