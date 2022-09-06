<?php

namespace Illuminate\Cache;

use Illuminate\Contracts\Cache\Store;
use Psr\SimpleCache\InvalidArgumentException;

class DependencyCache extends Repository {
    private const DEPENDENCY_CACHE_KEY_PREFIX = 'cache_dependency';

    /**
     * Built dependencies names
     * @var array
     */
    private array $dependenciesNames;

    /**
     * Create a new DependencyCache instance
     *
     * @param Store $store
     * @param array $dependenciesList
     * @param TagSet|null $tags
     */
    public function __construct(Store $store, array $dependenciesList, protected ?TagSet $tags = NULL)
    {
        parent::__construct($store);

        $this->dependenciesNames = self::buildDependenciesNames($dependenciesList);
    }

    /**
     * Store an item in the cache and create its dependencies.
     *
     * @param array|string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = NULL): bool
    {
        $result = parent::set($key, $value, $ttl);

        if ($result) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Store an item in the cache and create its dependencies.
     *
     * @param array|string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = NULL): bool
    {
        $result = parent::put($key, $value, $ttl);

        if ($result) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Store multiple items in the cache for a given number of seconds and create its dependencies.
     *
     * @param array $values
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @return bool
     */
    public function putMany(array $values, $ttl = NULL): bool
    {
        $result = parent::putMany($values, $ttl);

        foreach ($values as $key => $value) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Store multiple items in the cache indefinitely and create its dependencies.
     *
     * @param array $values
     * @return bool
     */
    public function putManyForever(array $values): bool
    {
        $result = parent::putManyForever($values);

        foreach ($values as $key => $value) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Store an item in the cache if the key does not exist and create its dependencies.
     *
     * @param string $key
     * @param mixed $value
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @return bool
     */
    public function add($key, $value, $ttl = NULL): bool
    {
        $result = parent::add($key, $value, $ttl);

        if ($result) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Store an item in the cache indefinitely and create its dependencies.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function forever($key, $value): bool
    {
        $result = parent::forever($key, $value);

        if ($result) {
            $this->createDependency($key);
        }

        return $result;
    }

    /**
     * Removes all items stored in cache assigned to dependencies
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function invalidate(): void
    {
        foreach ($this->dependenciesNames as $dependencyName) {
            $dependencyNameCacheKey = self::getCacheKeyForDependencyName($dependencyName);
            // Check, if cache key for dependency name exists
            if ($this->has($dependencyNameCacheKey)) {
                $dependencyCacheItems = $this->get($dependencyNameCacheKey);

                foreach ($dependencyCacheItems as $cachedItemKey => $cacheItem) {
                    // Removes cached items in taggable cache
                    foreach ($cacheItem['tags'] as $tagGroup) {
                        $this->tags(explode('|', $tagGroup))->delete($cachedItemKey);
                    }

                    // Removes cached item, which is not tagged by any tag
                    if ($cacheItem['rnt']) {
                        $this->delete($cachedItemKey);
                    }
                }

                // After invalidation, we remove dependency cache record
                $this->forget($dependencyNameCacheKey);
            }
        }
    }

    /**
     * Check if dependencies exists in cache store.
     *
     * @return bool
     */
    public function exists(): bool
    {
        foreach ($this->dependenciesNames as $dependencyName) {
            if (!$this->has(self::getCacheKeyForDependencyName($dependencyName))) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Store a dependency item in the cache with related cache item data
     *
     * @param $key
     * @return void
     */
    private function createDependency($key): void
    {
        foreach ($this->dependenciesNames as $dependencyName) {
            $dependencyNameCacheKey = self::getCacheKeyForDependencyName($dependencyName);

            if ($this->has($dependencyNameCacheKey)) {
                $dependencies = $this->get($dependencyNameCacheKey);
            }

            // rnt - shortcut for "remove non tagged" item. It means, if we want to remove cached item which is not assigned to any tag
            // tags - include existing tag groups assigned to cached item
            $dependencies[$key] = [
                'rnt' => empty($this->tags) ? TRUE : ($dependencies[$key]['rnt'] ?? FALSE),
                'tags' => $dependencies[$key]['tags'] ?? [],
            ];

            // Save cache tags if provided
            if ($this->tags?->getNames()) {
                $tags = implode('|', $this->tags->getNames());
                if (!empty($tags) && !in_array($tags, $dependencies[$key]['tags'])) {
                    $dependencies[$key]['tags'][] = $tags;
                }
            }

            parent::forever($dependencyNameCacheKey, $dependencies);
        }
    }

    /**
     * Dependencies names builder
     *
     * @param $dependencies
     * @return array
     */
    private static function buildDependenciesNames($dependencies): array
    {
        $builtDependenciesNames = [];

        foreach ($dependencies as $dependencyKey => $dependencyValues) {
            if (!is_array($dependencyValues)) {
                $builtDependenciesNames[] = $dependencyValues;

                continue;
            }

            foreach ($dependencyValues as $dependencyValue) {
                $builtDependenciesNames[] = implode('_', [$dependencyKey, $dependencyValue]);
            }
        }

        return $builtDependenciesNames;
    }

    /**
     * Generate cache key for dependency name
     *
     * @param $dependencyName
     * @return string
     */
    private static function getCacheKeyForDependencyName($dependencyName): string
    {
        return sha1(self::DEPENDENCY_CACHE_KEY_PREFIX) . ':' . $dependencyName;
    }
}
