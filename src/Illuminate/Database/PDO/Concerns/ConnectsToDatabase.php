<?php

namespace Illuminate\Database\PDO\Concerns;

use Illuminate\Database\PDO\Connection;
use InvalidArgumentException;
use PDO;

trait ConnectsToDatabase
{
    /**
     * Create a new database connection.
     *
     * @param  mixed[]  $params
     * @param  ?string  $username
     * @param  ?string  $password
     * @param  mixed[]  $driverOptions
     * @return \Illuminate\Database\PDO\Connection
     *
     * @throws \InvalidArgumentException
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        if (! isset($params['pdo']) || ! $params['pdo'] instanceof PDO) {
            throw new InvalidArgumentException('Laravel requires the "pdo" property to be set and be a PDO instance.');
        }

        return new Connection($params['pdo']);
    }
}
