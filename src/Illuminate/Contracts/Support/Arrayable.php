<?php

namespace Illuminate\Contracts\Support;

/**
 * @template-covariant TReturn of array = array<array-key, mixed>
 */
interface Arrayable
{
    /**
     * Get the instance as an array.
     *
     * @return TReturn
     */
    public function toArray();
}
