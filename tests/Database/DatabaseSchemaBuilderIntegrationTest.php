<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBuilderIntegrationTest extends TestCase
{
    protected $db;

    /**
     * Bootstrap database.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->setAsGlobal();

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testDropAllTablesWorksWithForeignKeys()
    {
        $this->db->connection()->getSchemaBuilder()->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
        });

        $this->db->connection()->getSchemaBuilder()->create('table2', function (Blueprint $table) {
            $table->integer('id');
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('table1');
        });

        $this->assertTrue($this->db->connection()->getSchemaBuilder()->hasTable('table1'));
        $this->assertTrue($this->db->connection()->getSchemaBuilder()->hasTable('table2'));

        $this->db->connection()->getSchemaBuilder()->dropAllTables();

        $this->assertFalse($this->db->connection()->getSchemaBuilder()->hasTable('table1'));
        $this->assertFalse($this->db->connection()->getSchemaBuilder()->hasTable('table2'));
    }

    public function testHasColumnWithTablePrefix()
    {
        $this->db->connection()->setTablePrefix('test_');

        $this->db->connection()->getSchemaBuilder()->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name');
        });

        $this->assertTrue($this->db->connection()->getSchemaBuilder()->hasColumn('table1', 'name'));
    }

    public function testHasColumnAndIndexWithPrefixIndexDisabled()
    {
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'example_',
            'prefix_indexes' => false,
        ]);

        $this->db->connection()->getSchemaBuilder()->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $this->assertArrayHasKey('table1_name_index', $this->db->connection()->getDoctrineSchemaManager()->listTableIndexes('example_table1'));
    }

    public function testHasColumnAndIndexWithPrefixIndexEnabled()
    {
        $this->db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'example_',
            'prefix_indexes' => true,
        ]);

        $this->db->connection()->getSchemaBuilder()->create('table1', function (Blueprint $table) {
            $table->integer('id');
            $table->string('name')->index();
        });

        $this->assertArrayHasKey('example_table1_name_index', $this->db->connection()->getDoctrineSchemaManager()->listTableIndexes('example_table1'));
    }
}
