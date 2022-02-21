<?php

namespace Illuminate\Contracts\Support;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray();
}
