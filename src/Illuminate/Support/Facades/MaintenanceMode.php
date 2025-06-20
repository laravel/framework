<?php

namespace Illuminate\Support\Facades;

class MaintenanceMode extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'maintenance.manager';
    }
}
