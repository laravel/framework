<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Illuminate\Database\PDO\Concerns\ConnectsToDatabase;

class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;

    /**
     * Gets the name of the driver.
     *
     * @deprecated
     *
     * @return string The name of the driver.
     */
    public function getName()
    {
        return 'pdo_mysql';
    }
}
