<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class DatabaseSqlServerMigrationsWithPrefixSchema extends DatabaseSqlServerMigrationTest
{

    public function recreateDatabase()
    {
        parent::recreateDatabase();
        DB::statement('CREATE SCHEMA test;');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        config()->set('database.connections.sqlsrv.prefix', 'test.');
    }

    public function testSimpleCreateTableMigration()
    {
        parent::testSimpleCreateTableMigration();
        /** @var Connection $con */
        $con = DB::connection();
        $con->setTablePrefix('');
        $con->getSchemaGrammar()->setTablePrefix('');
        $builder = $con->getSchemaBuilder();
        $this->assertTrue($builder->hasTable('test.users'));
        $this->assertFalse($builder->hasTable('users'));
        $this->assertFalse($builder->hasTable('dbo.users'));
    }
}
