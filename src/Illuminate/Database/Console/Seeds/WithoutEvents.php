<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Database\Eloquent\Model;

trait WithoutEvents
{
    /**
     * Prevent all event handles from being executed by the given callback.
     *
     * @param  callable  $callback
     * @return callable
     */
    public function withoutEvents(callable $callback)
    {
        return fn () => Model::withoutEvents($callback);
    }
}
