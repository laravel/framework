<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Console\Kernel;

class ConsoleApplicationTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new FooCommandStub);
    }

    public function test_artisan_call_using_command_name()
    {
        $exitCode = $this->artisan('foo:bar', [
            'id' => 1,
        ]);

        $this->assertEquals($exitCode, 0);
    }

    public function test_artisan_call_using_command_class()
    {
        $exitCode = $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ]);

        $this->assertEquals($exitCode, 0);
    }

    /**
     * @expectedException \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function test_artisan_call_invalid_command_name()
    {
        $this->artisan('foo:bars');
    }
}

class FooCommandStub extends Command
{
    protected $signature = 'foo:bar {id}';

    public function handle()
    {
        //
    }
}
