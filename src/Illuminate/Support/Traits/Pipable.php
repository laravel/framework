<?php

namespace Illuminate\Support\Traits;

trait Pipable
{
    /**
     * Pass the instance to the given callback and return the result.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }
}
