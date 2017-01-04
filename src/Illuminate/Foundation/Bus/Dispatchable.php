<?php

namespace Illuminate\Foundation\Bus;

trait Dispatchable
{
    /**
     * Dispatch the job with the given arguments.
     *
     * @return void
     */
    public static function dispatch()
    {
        return dispatch(new static(...func_get_args()));
    }
}
