<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;
use Doctrine\DBAL\Driver\Connection as ConnectionContract;

class SqlServerDriver extends AbstractSQLServerDriver
{
    /**
     * Create a new database connection.
     *
     * @param  mixed[]  $params
     * @return \Illuminate\Database\PDO\SqlServerConnection
     */
    public function connect(array $params): ConnectionContract
    {
        return new SqlServerConnection(
            new Connection($params['pdo'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdo_sqlsrv';
    }
}
