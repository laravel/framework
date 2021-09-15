<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;

class SqlServerDriver extends AbstractSQLServerDriver
{
    /**
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public function connect(array $params)
    {
        return new SqlServerConnection(
            new Connection($params['pdo'])
        );
    }
}
