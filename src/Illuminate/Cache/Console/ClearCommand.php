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
        $storeName = $this->hasArgument('store') ? $this->argument('store') : null;
        $tagsNames = $this->hasOption('tags') ? $this->option('tags') : null;

        $store = $this->cache->store($storeName);

        $this->laravel['events']->fire('cache:clearing', [$storeName, $tagsNames]);

        if (! is_null($tagsNames)) {
            $store->tags(explode(',', $tagsNames))->flush();

            $this->info(sprintf('Application cache tags "%s" cleared!', $tagsNames));
        } else {
            $store->flush();

            $this->info('Application cache cleared!');
        }

        $this->laravel['events']->fire('cache:cleared', [$storeName, $tagsNames]);
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
