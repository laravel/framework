<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    protected $aliases = ['app:foobar'];

    public function handle()
    {
        //
    }
}
