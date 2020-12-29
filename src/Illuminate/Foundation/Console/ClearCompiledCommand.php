<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class ClearCompiledCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'clear-compiled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the compiled class file';

    /**
     * Has OPCache extension.
     *
     * @var bool
     */
    protected $hasOpcache;

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->hasOpcache = function_exists('opcache_reset');

        if ($this->hasOpcache) {
            $this->description = 'Remove the compiled class file and clear OPCache';
        }

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (is_file($servicesPath = $this->laravel->getCachedServicesPath())) {
            @unlink($servicesPath);
        }

        if (is_file($packagesPath = $this->laravel->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }

        if ($this->hasOpcache) {
            @opcache_reset();
        }

        $message = $this->hasOpcache
            ? 'OPCache and compiled services and packages files removed!'
            : 'Compiled services and packages files removed!';

        $this->info($message);
    }
}
