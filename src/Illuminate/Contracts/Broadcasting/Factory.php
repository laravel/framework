<?php

namespace Illuminate\Contracts\Broadcasting;

use UnitEnum;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    public function connection(UnitEnum|string|null $name = null);
}
