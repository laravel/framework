<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Illuminate\Database\PDO\Concerns\ConnectsToDatabase;

class PostgresDriver extends AbstractPostgreSQLDriver
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
        return 'pdo_pgsql';
    }
}
