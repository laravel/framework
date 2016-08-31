<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ShowCommand extends Command
{
    /**
     * @var \Illuminate\Cache\CacheManager
     */
    private $manager;

    /**
     * @var string
     */
    protected $name = 'cache:show';

    /**
     * @var string
     */
    protected $description = 'Show the full cache.';

    /**
     * ShowCommand constructor.
     *
     * @param  \Illuminate\Cache\CacheManager $manager
     * @return void
     */
    public function __construct(CacheManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * When the command is executed.
     *
     * @return void
     */
    public function handle()
    {
        // The optional cache store you 
        // want to display.
        $store = $this->argument('store');

        // Retrieve the selected store or the
        // default caching instance.
        $cache = $this->manager->store($store);

        if ($this->getFullCache($cache) === false) {
            return $this->info('The cache store you entered could not be found.');
        }
    }

    /**
     * The command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputOption::REQUIRED, 'The cache store you want to display.']
        ];
    }

    /**
     * Retrieve the complete cache.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return mixed
     */
    private function getFullCache(Store $store)
    {
        switch (get_class($store)) {
            case 'MemcachedStore':
                return $this->fromMemcached($store);
            case 'RedisStore':
                return $this->fromRedis($store);
        }

        return false;
    }

    /**
     * Retrieve from Memcached.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return mixed
     */
    private function fromMemcached(Store $store)
    {
        return $store->getMemcached()->fetchAll();
    }

    /**
     * Retrieve from Redis.
     *
     * @param  \Illuminate\Contracts\Cache\Store  $store
     * @return mixed
     */
    private function fromRedis(Store $store)
    {
        $keys = $store->getRedis()->command('KEYS', ['*']);

        $array = [];

        if (!empty($keys)) {
            foreach ($keys as $key) {
                $array[$key] = $store->get($key);
            }
        }

        return $array;
    }
}