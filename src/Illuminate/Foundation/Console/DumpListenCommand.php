<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\ConsoleDumps\DumpRenderer;
use Illuminate\Foundation\ConsoleDumps\DumpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\VarDumper\Cloner\Data;

#[AsCommand(name: 'dump:listen')]
class DumpListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump:listen
                    {--host=127.0.0.1 : The host address to listen for dumps on}
                    {--port=9912 : The port to listen for dumps on}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for application dumps';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $server = $this->server();
        $renderer = $this->renderer();

        $server->start();

        $this->components->info("Listening for dumps on [{$server->getHost()}].");

        $server->listen(function (Data $data, array $context) use ($renderer) {
            $renderer->render($data, $context);
        });

        return self::SUCCESS;
    }

    /**
     * Create the dump server instance.
     *
     * @return DumpServer
     */
    protected function server()
    {
        return new DumpServer(sprintf(
            'tcp://%s:%d',
            $this->option('host'),
            $this->option('port'),
        ));
    }

    /**
     * Create the dump renderer instance.
     *
     * @return DumpRenderer
     */
    protected function renderer()
    {
        return new DumpRenderer(
            new CliDumper(
                $this->output,
                $this->laravel->basePath(),
                $this->laravel['config']->get('view.compiled'),
            ),
            $this->laravel->basePath(),
        );
    }
}
