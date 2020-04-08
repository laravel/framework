<?php

namespace Illuminate\Database\Drivers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Illuminate\Database\Schema\MySqlSchemaManager;

class MySqlDriver extends Driver
{
    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        return new MySqlSchemaManager($conn);
    }
}
