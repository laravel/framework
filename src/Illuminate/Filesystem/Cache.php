<?php

namespace Illuminate\Filesystem;

use Illuminate\Contracts\Cache\Repository;
use League\Flysystem\Cached\Storage\AbstractCache;

class Cache extends AbstractCache
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $repo;

    /**
     * The cache key.
     *
     * @var string
     */
    protected $key;

    /**
     * The cache expire in minutes.
     *
     * @var int
     */
    protected $expire;

    /**
     * Create a new cache instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $repo
     * @param string                                 $key
     * @param int|null                               $expire
     */
    public function __construct(Repository $repo, string $key = 'flysystem', int $expire = null)
    {
        $this->repo = $repo;
        $this->key = $key;

        if ($expire) {
            $this->expire = (int) ceil($expire / 60);
        }
    }

    /**
     * Load the cache.
     *
     * @return void
     */
    public function load()
    {
        $contents = $this->repo->get($this->key);

        if ($contents !== null) {
            $this->setFromStorage($contents);
        }
    }

    /**
     * Store the cache.
     *
     * @return void
     */
    public function save()
    {
        $contents = $this->getForStorage();

        if ($this->expire !== null) {
            $this->repo->put($this->key, $contents, $this->expire);
        } else {
            $this->repo->forever($this->key, $contents);
        }
    }
}
