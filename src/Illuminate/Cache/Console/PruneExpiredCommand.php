<?php

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cache:prune-expired')]
class PruneExpiredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:prune-expired {store? : The name of the store you would like to prune expired items from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired items from caches that do not remove them automatically (database only)';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return int|null
     */
    public function handle(CacheManager $cache)
    {
        $store = $cache->store($this->argument('store'))->getStore();

        if (! method_exists($store, 'pruneExpired')) {
            $this->components->error('The cache store does not support pruning expired items.');

            return 1;
        }

        $count = $store->pruneExpired();

        $this->components->info("Pruned [{$count}] expired cache items.");
    }
}
