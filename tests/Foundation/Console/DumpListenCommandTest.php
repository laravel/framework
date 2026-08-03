<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\DumpListenCommand;
use Illuminate\Foundation\ConsoleDumps\DumpServer;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DumpListenCommandTest extends TestCase
{
    public function test_it_is_registered_with_artisan()
    {
        $this->assertInstanceOf(
            DumpListenCommand::class,
            $this->app[Kernel::class]->all()['dump:listen'],
        );
    }

    public function test_it_listens_for_and_renders_dumps()
    {
        $server = new FakeDumpServer;

        $command = new class($server) extends DumpListenCommand
        {
            public function __construct(protected DumpServer $dumpServer)
            {
                parent::__construct();
            }

            protected function server()
            {
                return $this->dumpServer;
            }
        };

        $this->app[Kernel::class]->registerCommand($command);

        $this->assertSame(0, $this->app[Kernel::class]->call('dump:listen'));

        $output = $this->app[Kernel::class]->output();

        $this->assertStringContainsString('Listening for dumps on [tcp://127.0.0.1:9912].', $output);
        $this->assertStringContainsString('"Taylor"', $output);
        $this->assertStringContainsString('routes/web.php:10', $output);

        $this->assertSame(['start', 'listen'], $server->calls);
    }

    public function test_it_uses_the_configured_host_and_port()
    {
        $command = new class extends DumpListenCommand
        {
            public function serverForTesting()
            {
                return $this->server();
            }
        };

        $command->setInput(new ArrayInput([
            '--host' => 'localhost',
            '--port' => 9988,
        ], $command->getDefinition()));

        $this->assertSame('tcp://localhost:9988', $command->serverForTesting()->getHost());
    }
}

class FakeDumpServer extends DumpServer
{
    public array $calls = [];

    public function __construct()
    {
        //
    }

    public function start(): void
    {
        $this->calls[] = 'start';
    }

    public function listen(callable $callback): void
    {
        $this->calls[] = 'listen';

        $callback(
            (new VarCloner)->cloneVar(['name' => 'Taylor']),
            [
                'source' => [
                    'file' => base_path('routes/web.php'),
                    'file_relative' => 'routes/web.php',
                    'line' => 10,
                ],
            ],
            1,
        );
    }

    public function getHost(): string
    {
        return 'tcp://127.0.0.1:9912';
    }
}
