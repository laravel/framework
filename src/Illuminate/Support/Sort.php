<?php

namespace Illuminate\Support;

class Sort
{
    protected $parameters = [];

    /**
     * Add a sort in ascending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function asc($callback, $options = SORT_REGULAR)
    {
        return $this->sort($callback, $options);
    }

    /**
     * Add a sort in descending order using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @return static
     */
    public function desc($callback, $options = SORT_REGULAR)
    {
        return $this->sort($callback, $options, true);
    }

    /**
     * Add a sort using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public function sort($callback, $options = SORT_REGULAR, $descending = false)
    {
        $this->parameters[] = [$callback, $options, $descending ? SORT_DESC : SORT_ASC];

        return $this;
    }

    /**
     * Get all of the sort parameters as an array.
     *
     * @return array
     */
    public function all()
    {
        return $this->parameters;
    }
}
