<?php

namespace Illuminate\Database\PDO;

use Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
use PDO;

class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;
}
