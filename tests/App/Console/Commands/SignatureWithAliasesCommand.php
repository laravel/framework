<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foo:bar', aliases: ['bar:baz', 'baz:qux'])]
class SignatureWithAliasesCommand extends Command
{
    public function handle()
    {
    }
}
