<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\PendingCommand;
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

        $this->assertSame(0, $exitCode);
    }

    public function test_artisan_call_using_command_class()
    {
        $exitCode = $this->artisan(FooCommandStub::class, [
            'id' => 1,
        ]);

        $this->assertSame(0, $exitCode);
    }

    public function test_artisan_call_using_command_class_with_mocked_output()
    {
        $pendingCommand = $this->withMockedConsoleOutput()->artisan(FooCommandStub::class, ['id' => 1]);

        $this->assertInstanceOf(PendingCommand::class, $pendingCommand);

        $pendingCommand->assertExitCode(0);
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
