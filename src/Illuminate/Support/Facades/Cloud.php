<?php

namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Cloud\CloudManager;

/**
 * @see \Illuminate\Foundation\Cloud\CloudManager
 */
class Cloud extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return CloudManager::class;
    }
}
