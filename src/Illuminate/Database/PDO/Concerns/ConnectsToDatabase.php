<?php

namespace Illuminate\Database\PDO\Concerns;

use Illuminate\Database\PDO\Connection;
use PDO;

trait ConnectsToDatabase
{
    /**
     * Create a new database connection.
     *
     * @param  array  $params
     * @return \Illuminate\Database\PDO\Connection
     */
    public function connect(array $params)
    {
        if (! isset($params['pdo']) || ! $params['pdo'] instanceof PDO) {
            throw new \InvalidArgumentException('Laravel requires the "pdo" property to be set and be a PDO instance.');
        }

        return new Connection($params['pdo']);
    }
}
