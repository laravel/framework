<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ClearCommand extends Command
{
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
     * Create a new cache clear command instance.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return void
     */
    public function __construct(CacheManager $cache)
    {
        parent::__construct();

        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel['events']->fire('cache:clearing', [$this->argument('store'), $this->tags()]);

        $this->cache()->flush();

        $this->laravel['events']->fire('cache:cleared', [$this->argument('store'), $this->tags()]);

        $this->info('Cache cleared successfully.');
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
        return array_filter(explode(',', $this->option('tags')));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputArgument::OPTIONAL, 'The name of the store you would like to clear.'],
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
            ['tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear.', null],
        ];
    }
}
