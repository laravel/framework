<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
use PDO;

class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;
}
