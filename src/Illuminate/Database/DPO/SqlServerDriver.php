<?php

namespace Illuminate\Database\DPO;

use Doctrine\DBAL\Driver\AbstractSQLServerDriver;

class SqlServerDriver extends AbstractSQLServerDriver
{
    public function connect(array $params)
    {
        return new SqlServerConnection(
            new Connection($params)
        );
    }
}
