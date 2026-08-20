<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'zonda', aliases: ['app:zonda'])]
class ZondaCommandStub extends Command
{
    protected $signature = 'zonda {id}';

    protected $aliases = ['app:zonda'];

    public function handle()
    {
        //
    }
}
