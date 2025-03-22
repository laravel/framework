<?php

namespace Illuminate\Tests\Integration\Database\MySql;

use PDO;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresOperatingSystem('Linux|Darwin')]
#[RequiresPhpExtension('pdo_mysql')]
class DatabaseEmulatePreparesMySqlConnectionTest extends DatabaseMySqlConnectionTest
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.mysql.options', [
            PDO::ATTR_EMULATE_PREPARES => true,
        ]);
    }
}
