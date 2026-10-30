<?php

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cache:prune-stale-tags')]
class PruneStaleTagsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:prune-stale-tags {store? : The name of the store you would like to prune tags from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune stale cache tags from the cache (Redis only)';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return int|null
     */
    public function handle(CacheManager $cache)
    {
        $cache = $cache->store($this->argument('store'));

        if (method_exists($cache->getStore(), 'flushStaleTags')) {
            $cache->flushStaleTags();
        }

        $this->components->info('Stale cache tags pruned successfully.');
    }
}
