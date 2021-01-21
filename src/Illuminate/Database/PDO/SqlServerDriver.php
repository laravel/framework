<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;

class SqlServerDriver extends AbstractSQLServerDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        return new SqlServerConnection(
            new Connection($params['pdo'])
        );
    }

    /**
     * Gets the name of the driver.
     *
     * @deprecated
     *
     * @return string The name of the driver.
     */
    public function getName()
    {
        return 'pdo_sqlsrv';
    }
}
