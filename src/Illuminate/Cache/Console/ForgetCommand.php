<?php

namespace Illuminate\Cache\Console;

use Illuminate\Cache\CacheManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cache:forget')]
class ForgetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'cache:forget {key : The key to remove} {store? : The store to remove the key from}';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     *
     * @deprecated
     */
    protected static $defaultName = 'cache:forget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an item from the cache';

    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Cache\CacheManager
     */
    protected $cache;

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return void
     */
    public function handle(CacheManager $cache)
    {
        $this->cache = $cache;

        $this->cache->store($this->argument('store'))->forget(
            $this->argument('key')
        );

        $this->components->info('The ['.$this->argument('key').'] key has been removed from the cache.');
    }
}
