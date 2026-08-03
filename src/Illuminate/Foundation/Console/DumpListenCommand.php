<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
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
        $dumper = $this->dumper();

        $server->start();

        $this->components->info("Listening for dumps on [{$server->getHost()}].");

        $server->listen(function (Data $data, array $context) use ($dumper) {
            $this->renderDump($dumper, $data, $context);
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
     * Create the CLI dumper instance.
     *
     * @return CliDumper
     */
    protected function dumper()
    {
        return new CliDumper(
            $this->output,
            $this->laravel->basePath(),
            $this->laravel['config']->get('view.compiled'),
        );
    }

    /**
     * Render an incoming dump.
     *
     * @param  array<string, mixed>  $context
     * @return void
     */
    protected function renderDump(CliDumper $dumper, Data $data, array $context)
    {
        CliDumper::resolveDumpSourceUsing(fn () => $this->resolveSource($context));

        try {
            $dumper->dumpWithSource($data);
        } finally {
            CliDumper::resolveDumpSourceUsing(null);
        }
    }

    /**
     * Resolve the source from the dump context.
     *
     * @param  array<string, mixed>  $context
     * @return array{0: string, 1: string, 2: int|null}|null
     */
    protected function resolveSource(array $context)
    {
        $source = $context['source'] ?? [];
        $file = $source['file'] ?? null;

        if (! is_string($file)) {
            return null;
        }

        $relativeFile = is_string($source['file_relative'] ?? null)
            ? $source['file_relative']
            : $file;

        if (! isset($source['file_relative']) && str_starts_with($file, $this->laravel->basePath())) {
            $relativeFile = substr($file, strlen($this->laravel->basePath()) + 1);
        }

        $line = is_int($source['line'] ?? null) ? $source['line'] : null;

        return [$file, $relativeFile, $line];
    }
}
