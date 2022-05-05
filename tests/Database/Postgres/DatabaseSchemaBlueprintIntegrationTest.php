<?php

namespace Illuminate\Tests\Database\Postgres;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBlueprintIntegrationTest extends TestCase
{
    protected $db;

    /**
     * Bootstrap Eloquent.
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

    public function testRenameIndexWorks()
    {
        $this->db->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name');
            $table->string('age');
        });

        $this->db->connection()->getSchemaBuilder()->table('users', function ($table) {
            $table->index(['name'], 'index1');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->renameIndex('index1', 'index2');
        });

        $queries = $blueprint->toSql($this->db->connection(), new PostgresGrammar);

        $expected = [
            'alter index "index1" rename to "index2"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAddUniqueIndexWithoutNameWorks()
    {
        $this->db->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name')->nullable();
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique()->change();
        });

        $queries = $blueprint->toSql($this->db->connection(), new PostgresGrammar);

        $expected = [
            'CREATE TEMPORARY TABLE __temp__users AS SELECT name FROM users',
            'DROP TABLE users',
            'CREATE TABLE users (name VARCHAR(255) DEFAULT NULL COLLATE BINARY)',
            'INSERT INTO users (name) SELECT name FROM __temp__users',
            'DROP TABLE __temp__users',
            'alter table "users" add constraint "users_name_unique" unique ("name")',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAddUniqueIndexWithNameWorks()
    {
        $this->db->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name')->nullable();
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->unsignedInteger('name')->nullable()->unique('index1')->change();
        });

        $queries = $blueprint->toSql($this->db->connection(), new PostgresGrammar);

        $expected = [
            'CREATE TEMPORARY TABLE __temp__users AS SELECT name FROM users',
            'DROP TABLE users',
            'CREATE TABLE users (name INTEGER UNSIGNED DEFAULT NULL)',
            'INSERT INTO users (name) SELECT name FROM __temp__users',
            'DROP TABLE __temp__users',
            'alter table "users" add constraint "index1" unique ("name")',
        ];

        $this->assertEquals($expected, $queries);
    }
}
