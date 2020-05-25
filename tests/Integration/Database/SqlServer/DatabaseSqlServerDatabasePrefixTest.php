<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlServerDatabasePrefixTest extends DatabaseSqlServerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.connections.sqlsrv.prefix', 'test_');
        parent::getEnvironmentSetUp($app);
    }

    public function testCreateTableUsesPrefix()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('name');
        });
        $this->assertTrue(Schema::hasTable('users'));

        $this->assertTrue(Schema::hasColumn('users', 'name'));
    }


    public function testDropColumnUsesPrefix()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('name');
            $table->string('toDrop')->default('test');
        });
        $this->assertTrue(Schema::hasColumn('users', 'toDrop'));

        Schema::table('users', function (Blueprint $table){
            $table->dropColumn('toDrop');
        });
        $this->assertFalse(Schema::hasColumn('users', 'toDrop'));
    }
}
