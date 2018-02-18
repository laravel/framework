<?php

namespace Illuminate\Support;

interface ConnectionFactoryInterface
{
    /**
     * Make a driver instance using the provided configuration.
     *
     * @param  string  $driver
     * @param  array   $config
     * @return mixed
     */
    public function make($driver, array $config);
}
