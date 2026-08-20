<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foo:bar')]
#[Aliases(['bar:baz', 'baz:qux'])]
class AliasesAttributeCommand extends Command
{
    public function handle()
    {
    }
}
