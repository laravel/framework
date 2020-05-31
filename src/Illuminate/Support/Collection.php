<?php

namespace Illuminate\Support;

use Illuminate\Collections\Collection as BaseCollection;

/**
 * @deprecated Will be removed in a future Laravel version.
 */
class Collection extends BaseCollection
{
    /**
     * Collect the values into a collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collect()
    {
        return new self($this->all());
    }

    /**
     * Get a lazy collection for the items in this collection.
     *
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazy()
    {
        return new LazyCollection($this->items);
    }

    /**
     * Get a base Support collection instance from this collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function toBase()
    {
        return new self($this);
    }
}
