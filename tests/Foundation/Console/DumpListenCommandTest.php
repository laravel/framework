<?php

namespace Illuminate\Tests\Foundation\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\DumpListenCommand;
use Illuminate\Foundation\ConsoleDumps\DumpServer;
use Orchestra\Testbench\TestCase;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DumpListenCommandTest extends TestCase
{
    public function testItIsRegisteredWithArtisan()
    {
        $this->assertInstanceOf(
            DumpListenCommand::class,
            $this->app[Kernel::class]->all()['dump:listen'],
        );
    }

    public function testItListensForAndRendersDumps()
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
