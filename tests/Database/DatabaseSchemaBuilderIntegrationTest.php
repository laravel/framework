<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseSchemaBuilderIntegrationTest extends TestCase
{
    protected $db;

    /**
     * Bootstrap database.
     *
     * @return void
     */
    public function setUp()
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

    public function tearDown()
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testDropAllTablesWorksWithForeignKeys()
    {
        $this->db->connection()->getSchemaBuilder()->create('table1', function ($table) {
            $table->integer('id');
            $table->string('name');
        });

        $this->db->connection()->getSchemaBuilder()->create('table2', function ($table) {
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
}
