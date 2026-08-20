<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foo:bar', aliases: ['ignored:alias'])]
#[Aliases(['override:alias'])]
class AliasesAttributeOverridesSignatureCommand extends Command
{
    public function handle()
    {
    }
}
