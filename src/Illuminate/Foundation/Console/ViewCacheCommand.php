<?php

namespace Illuminate\Foundation\Console;

use RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\CompilerEngine;

class ViewCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:cache
                    {--f|force : Compile all views regardless of their timestamp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile all view files';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config clear command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $force = $this->option('force');

        $paths = $this->laravel['config']['view.paths'];
        if (! $paths) {
            throw new RuntimeException('View path not found.');
        }

        $factory = view();

        foreach ($paths as $path) {
            foreach ($this->files->allFiles($path) as $file) {
                $path = $file->getRealPath();

                $engine = $factory->getEngineFromPath($path);
                if (! ($engine instanceof CompilerEngine)) {
                    continue;
                }

                $compiler = $engine->getCompiler();
                if ($force || $compiler->isExpired($path)) {
                    $compiler->compile($path);
                    $this->info('Compiled view: '.$path, 'v');
                }
            }
        }

        $this->info('Views cached successfully!', 'normal');
    }
}
