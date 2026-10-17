<?php

namespace Illuminate\Cache;

use function Illuminate\Support\enum_value;

class BufferedTaggedCache extends MemoizedTaggedCache
{
    /**
     * The buffered store instance.
     *
     * @var \Illuminate\Cache\BufferedStore
     */
    protected BufferedStore $bufferedStore;

    /**
     * Create a new buffered tagged cache.
     */
    public function __construct(TaggedCache $taggedCache, BufferedStore $bufferedStore)
    {
        parent::__construct($taggedCache, $bufferedStore);

        $this->bufferedStore = $bufferedStore;

        $this->cache = &$bufferedStore->buffered();
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null): mixed
    {
        if (is_array($key)) {
            return $this->many($key);
        }

        $key = enum_value($key);

        $prefixedKey = $this->itemKey($key);

        $value = $this->bufferedStore->hit($prefixedKey)
            ? $this->cache[$prefixedKey]
            : $this->bufferedStore->buffer($prefixedKey, $this->taggedCache->get($key));

        return is_null($value) ? value($default) : $value;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @param  array  $defaults
     * @return array
     */
    public function many(array $defaults)
    {
        [$keys, $buffered, $missing] = [[], [], []];

        foreach ($defaults as $key => $value) {
            $key = array_is_list($defaults)
                ? enum_value($value)
                : enum_value($key);

            $keys[$key] = array_is_list($defaults) ? null : $value;

            $prefixedKey = $this->itemKey($key);

            if ($this->bufferedStore->hit($prefixedKey)) {
                $buffered[$key] = $this->cache[$prefixedKey];
            } else {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            foreach ($this->taggedCache->many($missing) as $key => $value) {
                $buffered[$key] = $this->bufferedStore->buffer($this->itemKey($key), $value);
            }
        }

        $result = [];

        foreach ($keys as $key => $default) {
            $result[$key] = array_key_exists($key, $buffered) && ! is_null($buffered[$key])
                ? $buffered[$key]
                : value($default);
        }

        return $result;
    }
}
