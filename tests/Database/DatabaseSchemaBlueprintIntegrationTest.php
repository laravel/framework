<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;
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

    public function testItEnsuresDroppingMultipleColumnsIsAvailable()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.");

        $this->db->connection()->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('email');
        });
    }

    public function testItEnsuresRenamingMultipleColumnsIsAvailable()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.");

        $this->db->connection()->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'first_name');
            $table->renameColumn('name2', 'last_name');
        });
    }

    public function testItEnsuresRenamingAndDroppingMultipleColumnsIsAvailable()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("SQLite doesn't support multiple calls to dropColumn / renameColumn in a single modification.");

        $this->db->connection()->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->renameColumn('name2', 'last_name');
        });
    }

    public function testItEnsuresDroppingForeignKeyIsAvailable()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("SQLite doesn't support dropping foreign keys (you would need to re-create the table).");

        $this->db->connection()->getSchemaBuilder()->table('users', function (Blueprint $table) {
            $table->dropForeign('something');
        });
    }
}
