<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Tests\Console\FooClassStub;

class ConsoleCommandStub extends Command
{
    protected $signature = 'foo:bar';

    protected $description = 'This is a description about the command';

    protected $foo;

    public function __construct(FooClassStub $foo)
    {
        parent::__construct();

        $this->foo = $foo;
    }
}
