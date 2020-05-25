<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSqlServerSchemaTest extends DatabaseSqlServerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
//        config()->set('database.sqlsrv.schema', 'test');
        parent::getEnvironmentSetUp($app);
    }


    public function testDropColumnWithDefaultAlsoDropsConstraintOnOtherSchema()
    {

        DB::statement('CREATE SCHEMA test;');
        /** @var DatabaseManager $db */
        // tests: SqlServerGrammar::compileDropDefaultConstraint()
        Schema::create('test.foo', function ($table) {
            $table->string('test');
            $table->boolean('bar')->default(false);
        });
        Schema::table('test.foo', function ($table) {
            $table->dropColumn('bar'); // Don't works!
        });
        $this->assertTrue(Schema::hasColumn('test.foo', 'test'));
        $this->assertFalse(Schema::hasColumn('test.foo', 'bar'));
    }

}
