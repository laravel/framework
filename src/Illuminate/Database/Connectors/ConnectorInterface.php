<?php

namespace Illuminate\Database\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a database connection.
     *
     * @return \PDO
     */
    public function connect(array $config);
}
