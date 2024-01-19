<?php

namespace Illuminate\Support\Traits;

trait Mappable
{
    /**
     * Call the given Closure with this instance then return new instance.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public function map($callback)
    {
        return map($this, $callback);
    }
}
