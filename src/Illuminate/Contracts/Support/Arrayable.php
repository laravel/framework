<?php

namespace Illuminate\Contracts\Support;

/**
 * @template TKey of array-key
 * @template TValue
 */
interface Arrayable<TKey = mixed, TValue = mixed>
{
    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray();
}
