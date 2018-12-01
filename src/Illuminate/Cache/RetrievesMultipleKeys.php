<?php

namespace Illuminate\Cache;

trait RetrievesMultipleKeys
{
    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  array  $keys
     * @return array
     */
    public function many(array $keys)
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * Store multiple items in the cache for a given number of minutes.
     *
     * @param  array  $values
     * @param  float|int  $minutes
     * @return bool
     */
    public function putMany(array $values, $minutes)
    {
        $resultMany = null;
        foreach ($values as $key => $value) {
            $result = $this->put($key, $value, $minutes);
            $resultMany = is_null($resultMany) ? $result : $result && $resultMany;
        }

        return $resultMany ?: false;
    }
}
