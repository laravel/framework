<?php

namespace Illuminate\Support\Facades;

/**
 * @see \Illuminate\Database\Eloquent\TypeCaster\Factory
 */
class TypeCaster extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'typecaster';
    }
}
