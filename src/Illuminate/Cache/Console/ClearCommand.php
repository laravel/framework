<?php

namespace Illuminate\Cache\Console;

use BadMethodCallException;
use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'cache:clear')]
class ClearCommand extends Command
{
    use Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the application cache';

    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new cache clear command instance.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(CacheManager $cache, Filesystem $files)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        if ($this->option('locks')) {
            return $this->clearLocks();
        }

        if ($this->option('prefix') && ! empty($this->tags())) {
            $this->components->error('Cache tags cannot be used when clearing by prefix.');

            return self::FAILURE;
        }

        $this->laravel['events']->dispatch(
            'cache:clearing', [$this->argument('store'), $this->tags()]
        );

        if ($this->flushesByPrefix()) {
            try {
                $successful = $this->cache()->flushPrefix();
            } catch (BadMethodCallException) {
                $this->components->error('This cache store does not support clearing by prefix.');

                return self::FAILURE;
            }
        } else {
            $successful = $this->cache()->flush();
        }

        $this->flushFacades();

        if (! $successful) {
            $this->components->error('Failed to clear cache. Make sure you have the appropriate permissions.');

            return self::FAILURE;
        }

        $this->laravel['events']->dispatch(
            'cache:cleared', [$this->argument('store'), $this->tags()]
        );

        $this->components->info('Application cache cleared successfully.');

        return self::SUCCESS;
    }

    /**
     * Clear all locks from the cache store.
     *
     * @return int
     */
    protected function clearLocks()
    {
        if ($this->option('prefix')) {
            $this->components->error('Cache locks cannot be used when clearing by prefix.');

            return self::FAILURE;
        }

        if (! empty($this->tags())) {
            $this->components->error('Cache tags cannot be used when clearing locks.');

            return self::FAILURE;
        }

        try {
            $successful = $this->cache()->flushLocks();
        } catch (BadMethodCallException) {
            $this->components->error('This cache store does not support clearing locks.');

            return self::FAILURE;
        }

        if (! $successful) {
            $this->components->error('Failed to clear cache locks. Make sure you have the appropriate permissions.');

            return self::FAILURE;
        }

        $this->components->info('Application cache locks cleared successfully.');

        return self::SUCCESS;
    }

    /**
     * Flush the real-time facades stored in the cache directory.
     *
     * @return void
     */
    public function flushFacades()
    {
        if (! $this->files->exists($storagePath = storage_path('framework/cache'))) {
            return;
        }

        foreach ($this->files->files($storagePath) as $file) {
            if (preg_match('/facade-.*\.php$/', $file)) {
                $this->files->delete($file);
            }
        }
    }

    /**
     * Get the cache instance for the command.
     *
     * @return \Illuminate\Cache\Repository
     */
    protected function cache()
    {
        $cache = $this->cache->store($this->argument('store'));

        return empty($this->tags()) ? $cache : $cache->tags($this->tags());
    }

    /**
     * Get the tags passed to the command.
     *
     * @return array
     */
    protected function tags()
    {
        return array_filter(explode(',', $this->option('tags') ?? ''));
    }

    /**
     * Determine if the cache store should be cleared by prefix.
     *
     * @return bool
     */
    protected function flushesByPrefix()
    {
        if ($this->option('prefix')) {
            return true;
        }

        if (! empty($this->tags()) || ! $this->laravel->bound('config')) {
            return false;
        }

        $store = $this->argument('store') ?: $this->laravel['config']->get('cache.default');

        return $this->laravel['config']->get("cache.stores.{$store}.flush_scope") === 'prefix';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear', null],
            ['prefix', null, InputOption::VALUE_NONE, 'Only clear cache entries matching the configured prefix'],
            ['locks', null, InputOption::VALUE_NONE, 'Only clear cache locks'],
        ];
    }
}
