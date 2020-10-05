<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
use PDO;

class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;
}
