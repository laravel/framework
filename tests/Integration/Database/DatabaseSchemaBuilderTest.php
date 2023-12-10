<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class DatabaseSchemaBuilderTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'database.default' => 'testing',
            'database.connections.sqlite-with-prefix' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => 'example_',
                'prefix_indexes' => false,
            ],
            'database.connections.sqlite-with-indexed-prefix' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => 'example_',
                'prefix_indexes' => true,
            ],
        ]);
    }

    public function testDropAllTablesWorksWithForeignKeys()
    {
        Schema::create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
        });

        Schema::create('table2', function (Blueprint $table) {
            $table->integer('id');
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('table1');
        });

        $this->assertTrue(Schema::hasTable('table1'));
        $this->assertTrue(Schema::hasTable('table2'));

        Schema::dropAllTables();

        $this->assertFalse(Schema::hasTable('table1'));
        $this->assertFalse(Schema::hasTable('table2'));
    }

    public function testHasColumnAndIndexWithPrefixIndexDisabled()
    {
        $connection = DB::connection('sqlite-with-prefix');

        Schema::connection('sqlite-with-prefix')->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $indexes = array_column($connection->getSchemaBuilder()->getIndexes('table1'), 'name');

        $this->assertContains('table1_name_index', $indexes, 'name');
    }

    public function testHasColumnAndIndexWithPrefixIndexEnabled()
    {
        $connection = DB::connection('sqlite-with-indexed-prefix');

        Schema::connection('sqlite-with-indexed-prefix')->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $indexes = array_column($connection->getSchemaBuilder()->getIndexes('table1'), 'name');

        $this->assertContains('example_table1_name_index', $indexes);
    }
}
