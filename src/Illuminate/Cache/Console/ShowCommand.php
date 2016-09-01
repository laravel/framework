<?php

namespace Illuminate\Cache\Console;

use Illuminate\Console\Command;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Symfony\Component\Console\Input\InputArgument;

class ShowCommand extends Command
{
    /**
     * @var \Illuminate\Cache\CacheManager
     */
    private $manager;

    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $store;

    /**
     * @var string
     */
    protected $name = 'cache:show';

    /**
     * @var string
     */
    protected $description = 'Show the full cache for a given repository.';

    /**
     * @var array
     */
    protected $headers = ['Key', 'Value'];

    /**
     * ShowCommand constructor.
     *
     * @param  \Illuminate\Cache\CacheManager $manager
     * @return void
     */
    public function __construct(CacheManager $manager)
    {
        parent::__construct();

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
        $this->store = $this->manager->store($store);

        if (($cached = $this->getFullCache($store)) === false) {
            return $this->info('The cache store you entered could not be found.');
        }

        $this->table($this->headers, $cached);
    }

    /**
     * The command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['store', InputArgument::REQUIRED, 'The cache store you want to display.'],
        ];
    }

    /**
     * Retrieve the complete cache.
     *
     * @param  string  $store
     * @return mixed
     */
    private function getFullCache($store)
    {
        switch ($store) {
            case 'memcached':
                return $this->fromMemcached();
            case 'redis':
                return $this->fromRedis();
        }

        return [];
    }

    /**
     * Retrieve from Memcached.
     *
     * @return mixed
     */
    private function fromMemcached()
    {
        $keys = $this->store->getMemcached()->getAllKeys();

        $array = [];

        // If no keys are found then just
        // return an empty array.
        if (is_null($keys) || $keys === false) {
            return $array;
        }

        // Iterate over all the returned keys
        // and push them into an array.
        foreach ($keys as $key) {
            if (strpos($key, 'laravel') === false) {
                continue;
            }

            // Remove the prefix from the key name
            // before looking the key up in the store.
            $key = str_replace($this->store->getPrefix(), '', $key);

            $array[] = [$key, $this->store->get($key)];
        }

        return $array;
    }

    /**
     * Retrieve from Redis.
     *
     * @return mixed
     */
    private function fromRedis()
    {
        $keys = $this->store->connection()->executeRaw(['keys', '*']);

        $array = [];

        // If no keys are found then just
        // return an empty array.
        if (empty($keys)) {
            return $array;
        }

        // Iterate over all the returned keys
        // and push them into an array.
        foreach ($keys as $key) {
            $array[] = [
                $key, $this->store->connection()->executeRaw(['get', $key]),
            ];
        }

        return $array;
    }
}