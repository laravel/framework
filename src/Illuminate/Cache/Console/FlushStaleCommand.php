<?php

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'cache:flush-stale')]
class FlushStaleCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cache:flush-stale {store? : The store to clean up}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'cache:flush-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flushes any stale data from the cache';

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
        $this->laravel['events']->dispatch(
            'cache:flushing-stale', [$this->argument('store')]
        );

        $repository = $this->cache->store($this->argument('store'));

        // Adding a flushStale() empty method into Store contract would be a breaking change,
        // so instead we're just checking whether it implements the method directly.
        if (!$repository->supportsFlushingStale()) {
            return $this->warn('Given store does not support flushing stale data. Make sure the correct store name was given.');
        }

        $repository->flushStale();

        $this->laravel['events']->dispatch(
            'cache:flushed-stale', [$this->argument('store')]
        );

        $this->info('Flushed stale cache data successfully.');
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
}
