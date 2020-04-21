<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Collections\LazyCollection as BaseLazyCollection;

class LazyCollection extends BaseLazyCollection
{
    /**
     * Create a new lazy collection instance.
     *
     * @param  mixed  $source
     * @return void
     */
    public function __construct($source = null)
    {
        if ($source instanceof Closure || $source instanceof self || $source instanceof BaseLazyCollection) {
            $this->source = $source;
        } elseif (is_null($source)) {
            $this->source = static::empty();
        } else {
            $this->source = $this->getArrayableItems($source);
        }
    }

    /**
     * Collect the values into a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collect()
    {
        return new Collection($this->all());
    }
}
