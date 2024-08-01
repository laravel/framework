<?php

namespace Illuminate\Support\Facades;

/**
 * @mixin \Illuminate\Log\Context\Repository
 */
class Context extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Illuminate\Log\Context\Repository::class;
    }
}
