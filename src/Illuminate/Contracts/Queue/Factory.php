<?php

namespace Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
