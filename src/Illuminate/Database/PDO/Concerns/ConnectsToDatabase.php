<?php

namespace Illuminate\Database\PDO\Concerns;

use Doctrine\DBAL\Driver\Connection as ConnectionContract;
use Illuminate\Database\PDO\Connection;
use InvalidArgumentException;
use PDO;

trait ConnectsToDatabase
{
    /**
     * Create a new database connection.
     *
     * @param  mixed[]  $params
     * @return \Illuminate\Database\PDO\Connection
     *
     * @throws \InvalidArgumentException
     */
    public function connect(array $params): ConnectionContract
    {
        if (! isset($params['pdo']) || ! $params['pdo'] instanceof PDO) {
            throw new InvalidArgumentException('Laravel requires the "pdo" property to be set and be a PDO instance.');
        }

        return new Connection($params['pdo']);
    }
}
