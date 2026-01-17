<?php

namespace Illuminate\Support\Facades;

use Illuminate\Support\Caster;

class Cast extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Caster::class;
    }
}
