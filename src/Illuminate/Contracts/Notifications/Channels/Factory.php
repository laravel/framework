<?php

namespace Illuminate\Contracts\Notifications\Channels;

interface Factory
{
    /**
     * Check for the driver capacity.
     *
     * @param  string  $driver
     * @return bool
     */
    public static function canHandleNotification($driver);

    /**
     * Create a new driver instance.
     *
     * @param  $driver
     * @return \Illuminate\Contracts\Notifications\Channels\Dispatcher
     */
    public static function createDriver($driver);
}