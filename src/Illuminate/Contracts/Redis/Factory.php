<?php

namespace Illuminate\Contracts\Redis;

interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string|null|\UnitEnum  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null);
}
