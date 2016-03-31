<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bootstrap\DetectEnvironment;

class ConfigCacheCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'config:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a cache file for faster configuration loading';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new config cache command instance.
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
     * Clear the environment.
     *
     * @return void
     */
    private function clearEnvironment()
    {
        foreach ($_ENV as $key => $value) {
            putenv("$key");
            unset($_SERVER[$key]);
        }

        $_ENV = [];
    }

    /**
     * Export environment to php code.
     *
     * @param $env
     * @return string
     */
    private function exportEnvironment($env)
    {
        $code = '';

        foreach ($env as $key => $value) {
            $code .= sprintf('putenv("%s=%s"); $_ENV["%s"] = "%s"; $_SERVER["%s"] = "%s";%s', $key, $value, $key, $value, $key, $value, PHP_EOL);
        }

        return $code;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->call('config:clear');

        $config = var_export($this->getFreshConfiguration(), true);

        $env = $this->exportEnvironment($this->getFreshEnvironment());

        $code = sprintf('<?php %s%s%sreturn %s;%s', PHP_EOL, $env, PHP_EOL, $config, PHP_EOL);

        $this->files->put($this->laravel->getCachedConfigPath(), $code);

        $this->info('Configuration cached successfully!');
    }

    /**
     * Boot a fresh copy of the application environment.
     *
     * @return string
     */
    private function getFreshEnvironment()
    {
        $this->clearEnvironment();

        $detector = $this->laravel->make(DetectEnvironment::class);

        return $detector->loadEnvironment($this->laravel);
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        $app = require $this->laravel->bootstrapPath().'/app.php';

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app['config']->all();
    }
}
