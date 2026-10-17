<?php

namespace Illuminate\Cache;

use InvalidArgumentException;

class BufferedStore extends MemoizedStore
{
    /**
     * The maximum number of buffered values.
     *
     * @var int
     */
    protected $size;

    /**
     * Create a new buffered cache instance.
     *
     * @param  string  $name
     * @param  \Illuminate\Cache\Repository  $repository
     * @param  int  $size
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $repository, $size = 1000)
    {
        parent::__construct($name, $repository);

        $this->size = (int) $size;

        if ($this->size < 1) {
            throw new InvalidArgumentException('The buffer size must be at least 1.');
        }
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        $prefixedKey = $this->prefix($key);

        if ($this->hit($prefixedKey)) {
            return $this->cache[$prefixedKey];
        }

        return $this->buffer($prefixedKey, $this->repository->get($key));
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @return array
     */
    public function many(array $keys)
    {
        [$result, $missing] = [[], []];

        foreach ($keys as $key) {
            $prefixedKey = $this->prefix($key);

            if ($this->hit($prefixedKey)) {
                $result[$key] = $this->cache[$prefixedKey];
            } else {
                $result[$key] = null;
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            foreach ($this->repository->many($missing) as $key => $value) {
                $result[$key] = $this->buffer($this->prefix($key), $value);
            }
        }

        return $result;
    }

    /**
     * Begin executing a new tags operation.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\TaggedCache
     *
     * @throws \BadMethodCallException
     */
    public function tags($names)
    {
        $names = is_array($names) ? $names : func_get_args();

        return $this->taggedCaches[serialize($names)] ??= new BufferedTaggedCache(
            $this->repository->tags($names), $this
        );
    }

    /**
     * Determine if the given key is buffered, moving it to the top of the buffer.
     *
     * @internal
     *
     * @param  string  $prefixedKey
     * @return bool
     */
    public function hit($prefixedKey)
    {
        if (! array_key_exists($prefixedKey, $this->cache)) {
            return false;
        }

        $value = $this->cache[$prefixedKey];

        unset($this->cache[$prefixedKey]);

        $this->cache[$prefixedKey] = $value;

        return true;
    }

    /**
     * Place a value at the top of the buffer, evicting the least recently used value when full.
     *
     * @internal
     *
     * @param  string  $prefixedKey
     * @param  mixed  $value
     * @return mixed
     */
    public function buffer($prefixedKey, $value)
    {
        unset($this->cache[$prefixedKey]);

        $this->cache[$prefixedKey] = $value;

        if (count($this->cache) > $this->size) {
            unset($this->cache[array_key_first($this->cache)]);
        }

        return $value;
    }

    /**
     * Get the buffered values by reference, so tagged caches share the buffer and its size.
     *
     * The buffer is a plain array, so it must be returned by reference for tagged caches to
     * operate on the same values instead of a copy, including their inherited memo writes.
     *
     * @internal
     *
     * @return array<string, mixed>
     */
    public function &buffered()
    {
        return $this->cache;
    }
}
