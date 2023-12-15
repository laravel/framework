<?php

namespace Illuminate\Contracts\Database\Connectors;

interface ConnectionFactory
{
    /**
     * Establish a PDO connection based on the configuration.
     *
     * @param  array  $config
     * @param  string  $name
     * @return \Illuminate\Database\Connection
     */
    public function make(array $config, $name);
}
