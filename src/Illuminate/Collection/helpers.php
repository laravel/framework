<?php

use Illuminate\Collection\Collection;

if (! function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \Illuminate\Collection\Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}
