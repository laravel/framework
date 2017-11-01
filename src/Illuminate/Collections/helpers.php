<?php

use Illuminate\Support\Collection;

if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Collections\Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}
