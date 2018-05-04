<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;

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
     * Serialize the records.
     *
     * @param  array $data
     * @return array
     */
    public static function transformClosure(array $data)
    {
        $serializer = new \SuperClosure\Serializer;

        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $data[$key] = static::transformClosure($val);
            }

            if ($val instanceof \Closure) {
                $data[$key] = $serializer->serialize($val);
            }
        }

        return $data;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('config:clear');

        $config = static::transformClosure($this->getFreshConfiguration());

        $this->files->put(
            $this->laravel->getCachedConfigPath(), '<?php return '.var_export($config, true).';'.PHP_EOL
        );

        $this->info('Configuration cached successfully!');
    }

    /**
     * Boot a fresh copy of the application configuration.
     *
     * @return array
     */
    protected function getFreshConfiguration()
    {
        $app = require $this->laravel->bootstrapPath().'/app.php';

        $app->make(ConsoleKernelContract::class)->bootstrap();

        return $app['config']->all();
    }
}
