<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ObserverClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'observer:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cached observers';

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
     *
     * @throws \RuntimeException
     */
    public function handle()
    {
        $this->files->delete($this->laravel->getCachedObserversPath());

        $this->info('Cached observers cleared!');
    }
}
