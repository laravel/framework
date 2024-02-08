<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class SchemaBuilderSchemaNameTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if (! in_array($this->driver, ['pgsql', 'sqlsrv'])) {
            $this->markTestSkipped('Test requires a PostgreSQL or SQL Server connection.');
        }

        DB::statement('create schema my_schema');
    }

    protected function destroyDatabaseMigrations()
    {
        DB::statement('drop schema my_schema');
    }

    protected function defineEnvironment($app)
    {
        $this->app['config']->set(
            'database.connections.without-prefix', $this->app['config']->get('database.connections.'.$this->driver)
        );
        $this->app['config']->set('database.connections.with-prefix', $this->app['config']->get('database.connections.without-prefix'));
        $this->app['config']->set('database.connections.with-prefix.prefix', 'example_');
    }

    #[DataProvider('schemaProvider')]
    public function testCreate(Builder $schema)
    {
        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });

        var_dump($schema->getTables());

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    public static function schemaProvider(): array
    {
        return [
            'without prefix' => [Schema::connection('without-prefix')],
            'with prefix' => [Schema::connection('with-prefix')]
        ];
    }
}
