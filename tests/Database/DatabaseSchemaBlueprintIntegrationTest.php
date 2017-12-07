<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseSchemaBlueprintIntegrationTest extends TestCase
{
    protected $db;

    /**
     * Bootstrap Eloquent.
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

        $container = new Illuminate\Container\Container;
        $container->instance('db', $db->getDatabaseManager());
        Illuminate\Support\Facades\Facade::setFacadeApplication($container);
    }

    public function tearDown()
    {
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication(null);
    }

    public function testRenamingAndChangingColumnsWork()
    {
        $this->db->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name');
            $table->string('age');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->renameColumn('name', 'first_name');
            $table->integer('age')->change();
        });

        $queries = $blueprint->toSql($this->db->connection(), new \Illuminate\Database\Schema\Grammars\SQLiteGrammar);

        $expected = [
            'CREATE TEMPORARY TABLE __temp__users AS SELECT name, age FROM users',
            'DROP TABLE users',
            'CREATE TABLE users (name VARCHAR(255) NOT NULL COLLATE BINARY, age INTEGER NOT NULL COLLATE BINARY)',
            'INSERT INTO users (name, age) SELECT name, age FROM __temp__users',
            'DROP TABLE __temp__users',
            'CREATE TEMPORARY TABLE __temp__users AS SELECT name, age FROM users',
            'DROP TABLE users',
            'CREATE TABLE users (age VARCHAR(255) NOT NULL COLLATE BINARY, first_name VARCHAR(255) NOT NULL)',
            'INSERT INTO users (first_name, age) SELECT name, age FROM __temp__users',
            'DROP TABLE __temp__users',
        ];

        $this->assertEquals($expected, $queries);
    }
}
