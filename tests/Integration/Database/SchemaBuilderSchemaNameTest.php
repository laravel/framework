<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

#[RequiresDatabase(['pgsql', 'sqlsrv'])]
class SchemaBuilderSchemaNameTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrations()
    {
        if ($this->driver === 'pgsql') {
            DB::connection('without-prefix')->statement('create schema if not exists my_schema');
            DB::connection('with-prefix')->statement('create schema if not exists my_schema');
        } elseif ($this->driver === 'sqlsrv') {
            DB::connection('without-prefix')->statement("if schema_id('my_schema') is null begin exec('create schema my_schema') end");
            DB::connection('with-prefix')->statement("if schema_id('my_schema') is null begin exec('create schema my_schema') end");
        }
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.connections.pgsql.search_path', 'public,my_schema');
        $app['config']->set('database.connections.without-prefix', $app['config']->get('database.connections.'.$this->driver));
        $app['config']->set('database.connections.with-prefix', $app['config']->get('database.connections.without-prefix'));
        $app['config']->set('database.connections.with-prefix.prefix', 'example_');
    }

    #[DataProvider('connectionProvider')]
    public function testCreate($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testRename($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('my_schema.new_table'));
        $this->assertTrue($schema->hasTable('table'));
        $this->assertFalse($schema->hasTable('my_table'));

        $schema->rename('my_schema.table', 'new_table');
        $schema->rename('table', 'my_table');

        $this->assertTrue($schema->hasTable('my_schema.new_table'));
        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('my_table'));
        $this->assertFalse($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testDrop($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));

        $schema->drop('my_schema.table');

        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testDropIfExists($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));

        $schema->dropIfExists('my_schema.table');
        $schema->dropIfExists('my_schema.fake_table');
        $schema->dropIfExists('fake_schema.table');

        $this->assertFalse($schema->hasTable('my_schema.table'));
        $this->assertTrue($schema->hasTable('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testAddColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('default schema title');
        });
        $schema->create('my_table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
        });

        $this->assertEquals(['id', 'title'], $schema->getColumnListing('my_schema.table'));
        $this->assertEquals(['id', 'name'], $schema->getColumnListing('my_table'));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->string('name')->default('default schema name');
            $table->integer('count');
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->integer('count');
            $table->string('title')->default('default title');
        });

        $this->assertEquals(['id', 'title', 'name', 'count'], $schema->getColumnListing('my_schema.table'));
        $this->assertEquals(['id', 'name', 'count', 'title'], $schema->getColumnListing('my_table'));
        $this->assertStringContainsString('default schema name', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'name')['default']);
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'title')['default']);
        $this->assertStringContainsString('default name', collect($schema->getColumns('my_table'))->firstWhere('name', 'name')['default']);
        $this->assertStringContainsString('default title', collect($schema->getColumns('my_table'))->firstWhere('name', 'title')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testRenameColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('default schema title');
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
        });

        $this->assertTrue($schema->hasColumn('my_schema.table', 'title'));
        $this->assertTrue($schema->hasColumn('table', 'name'));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->renameColumn('title', 'new_title');
        });
        $schema->table('table', function (Blueprint $table) {
            $table->renameColumn('name', 'new_name');
        });

        $this->assertFalse($schema->hasColumn('my_schema.table', 'title'));
        $this->assertTrue($schema->hasColumn('my_schema.table', 'new_title'));
        $this->assertFalse($schema->hasColumn('table', 'name'));
        $this->assertTrue($schema->hasColumn('table', 'new_name'));
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'new_title')['default']);
        $this->assertStringContainsString('default name', collect($schema->getColumns('table'))->firstWhere('name', 'new_name')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testModifyColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('count');
        });
        $schema->create('my_table', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('count');
        });

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->string('name')->default('default schema name')->change();
            $table->bigInteger('count')->change();
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->string('title')->default('default title')->change();
            $table->bigInteger('count')->change();
        });

        $this->assertStringContainsString('default schema name', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'name')['default']);
        $this->assertStringContainsString('default title', collect($schema->getColumns('my_table'))->firstWhere('name', 'title')['default']);
        $this->assertEquals($this->driver === 'sqlsrv' ? 'nvarchar' : 'varchar', $schema->getColumnType('my_schema.table', 'name'));
        $this->assertEquals($this->driver === 'sqlsrv' ? 'nvarchar' : 'varchar', $schema->getColumnType('my_table', 'title'));
        $this->assertEquals($this->driver === 'pgsql' ? 'int8' : 'bigint', $schema->getColumnType('my_schema.table', 'count'));
        $this->assertEquals($this->driver === 'pgsql' ? 'int8' : 'bigint', $schema->getColumnType('my_table', 'count'));
    }

    #[DataProvider('connectionProvider')]
    public function testDropColumns($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default schema name');
            $table->integer('count')->default(20);
            $table->string('title')->default('default schema title');
        });
        $schema->create('table', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('default name');
            $table->integer('count')->default(10);
            $table->string('title')->default('default title');
        });

        $this->assertTrue($schema->hasColumns('my_schema.table', ['id', 'name', 'count', 'title']));
        $this->assertTrue($schema->hasColumns('table', ['id', 'name', 'count', 'title']));

        $schema->dropColumns('my_schema.table', ['name', 'count']);
        $schema->dropColumns('table', ['name', 'title']);

        $this->assertTrue($schema->hasColumns('my_schema.table', ['id', 'title']));
        $this->assertFalse($schema->hasColumn('my_schema.table', 'name'));
        $this->assertFalse($schema->hasColumn('my_schema.table', 'count'));
        $this->assertTrue($schema->hasColumns('table', ['id', 'count']));
        $this->assertFalse($schema->hasColumn('table', 'name'));
        $this->assertFalse($schema->hasColumn('table', 'title'));
        $this->assertStringContainsString('default schema title', collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'title')['default']);
        $this->assertStringContainsString('10', collect($schema->getColumns('table'))->firstWhere('name', 'count')['default']);
    }

    #[DataProvider('connectionProvider')]
    public function testIndexes($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('email')->unique();
            $table->integer('name')->index();
            $table->integer('title')->index();
        });
        $schema->create('my_table', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('email')->unique();
            $table->integer('name')->index();
            $table->integer('title')->index();
        });

        $this->assertTrue($schema->hasIndex('my_schema.table', ['code'], 'primary'));
        $this->assertTrue($schema->hasIndex('my_schema.table', ['email'], 'unique'));
        $this->assertTrue($schema->hasIndex('my_schema.table', ['name']));
        $this->assertTrue($schema->hasIndex('my_table', ['code'], 'primary'));
        $this->assertTrue($schema->hasIndex('my_table', ['email'], 'unique'));
        $this->assertTrue($schema->hasIndex('my_table', ['name']));

        $schemaIndexName = $connection === 'with-prefix' ? 'my_schema_example_table_title_index' : 'my_schema_table_title_index';
        $indexName = $connection === 'with-prefix' ? 'example_my_table_title_index' : 'my_table_title_index';

        $schema->table('my_schema.table', function (Blueprint $table) use ($schemaIndexName) {
            $table->renameIndex($schemaIndexName, 'schema_new_index_name');
        });
        $schema->table('my_table', function (Blueprint $table) use ($indexName) {
            $table->renameIndex($indexName, 'new_index_name');
        });

        $this->assertTrue($schema->hasIndex('my_schema.table', 'schema_new_index_name'));
        $this->assertFalse($schema->hasIndex('my_schema.table', $schemaIndexName));
        $this->assertTrue($schema->hasIndex('my_table', 'new_index_name'));
        $this->assertFalse($schema->hasIndex('my_table', $indexName));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->dropPrimary(['code']);
            $table->dropUnique(['email']);
            $table->dropIndex(['name']);
            $table->dropIndex('schema_new_index_name');
        });
        $schema->table('my_table', function (Blueprint $table) {
            $table->dropPrimary(['code']);
            $table->dropUnique(['email']);
            $table->dropIndex(['name']);
            $table->dropIndex('new_index_name');
        });

        $this->assertEmpty($schema->getIndexListing('my_schema.table'));
        $this->assertEmpty($schema->getIndexListing('my_table'));
    }

    #[DataProvider('connectionProvider')]
    public function testForeignKeys($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_tables', function (Blueprint $table) {
            $table->id();
        });
        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('my_table_id')->constrained();
        });
        $schema->create('table', function (Blueprint $table) {
            $table->unsignedBigInteger('table_id');
            $table->foreign('table_id')->references('id')->on('my_schema.table');
        });

        $schemaTableName = $connection === 'with-prefix' ? 'example_table' : 'table';
        $tableName = $connection === 'with-prefix' ? 'example_my_tables' : 'my_tables';

        $this->assertTrue(collect($schema->getForeignKeys('my_schema.table'))->contains(
            fn ($foreign) => $foreign['columns'] === ['my_table_id']
                && $foreign['foreign_table'] === $tableName && in_array($foreign['foreign_schema'], ['public', 'dbo'])
                && $foreign['foreign_columns'] === ['id']
        ));

        $this->assertTrue(collect($schema->getForeignKeys('table'))->contains(
            fn ($foreign) => $foreign['columns'] === ['table_id']
                && $foreign['foreign_table'] === $schemaTableName && $foreign['foreign_schema'] === 'my_schema'
                && $foreign['foreign_columns'] === ['id']
        ));

        $schema->table('my_schema.table', function (Blueprint $table) {
            $table->dropForeign(['my_table_id']);
        });
        $schema->table('table', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
        });

        $this->assertEmpty($schema->getForeignKeys('my_schema.table'));
        $this->assertEmpty($schema->getForeignKeys('table'));
    }

    #[DataProvider('connectionProvider')]
    public function testHasView($connection)
    {
        $connection = DB::connection($connection);
        $schema = $connection->getSchemaBuilder();

        $connection->statement('create view '.$connection->getSchemaGrammar()->wrapTable('my_schema.view').' (name) as select 1');
        $connection->statement('create view '.$connection->getSchemaGrammar()->wrapTable('my_view').' (name) as select 1');

        $this->assertTrue($schema->hasView('my_schema.view'));
        $this->assertTrue($schema->hasView('my_view'));
        $this->assertTrue($schema->hasColumn('my_schema.view', 'name'));
        $this->assertTrue($schema->hasColumn('my_view', 'name'));

        $connection->statement('drop view '.$connection->getSchemaGrammar()->wrapTable('my_schema.view'));
        $connection->statement('drop view '.$connection->getSchemaGrammar()->wrapTable('my_view'));

        $this->assertFalse($schema->hasView('my_schema.view'));
        $this->assertFalse($schema->hasView('my_view'));
    }

    #[DataProvider('connectionProvider')]
    #[RequiresDatabase('pgsql')]
    public function testComment($connection)
    {
        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->comment('comment on schema table');
            $table->string('name')->comment('comment on schema column');
        });
        $schema->create('table', function (Blueprint $table) {
            $table->comment('comment on table');
            $table->string('name')->comment('comment on column');
        });

        $tables = collect($schema->getTables());
        $tableName = $connection === 'with-prefix' ? 'example_table' : 'table';

        $this->assertEquals('comment on schema table',
            $tables->first(fn ($table) => $table['name'] === $tableName && $table['schema'] === 'my_schema')['comment']
        );
        $this->assertEquals('comment on table',
            $tables->first(fn ($table) => $table['name'] === $tableName && $table['schema'] === 'public')['comment']
        );
        $this->assertEquals('comment on schema column',
            collect($schema->getColumns('my_schema.table'))->firstWhere('name', 'name')['comment']
        );
        $this->assertEquals('comment on column',
            collect($schema->getColumns('table'))->firstWhere('name', 'name')['comment']
        );
    }

    #[DataProvider('connectionProvider')]
    #[RequiresDatabase('pgsql')]
    public function testAutoIncrementStartingValue($connection)
    {
        $this->expectNotToPerformAssertions();

        $schema = Schema::connection($connection);

        $schema->create('my_schema.table', function (Blueprint $table) {
            $table->increments('code')->from(25);
        });
        $schema->create('table', function (Blueprint $table) {
            $table->increments('code')->from(15);
        });
    }

    #[DataProvider('connectionProvider')]
    #[RequiresDatabase('sqlsrv')]
    public function testHasTable($connection)
    {
        $db = DB::connection($connection);
        $schema = $db->getSchemaBuilder();

        try {
            $db->statement("create login my_user with password = 'Passw0rd'");
            $db->statement('create user my_user for login my_user');
        } catch(\Illuminate\Database\QueryException $e) {
            //
        }

        $db->statement('grant create table to my_user');
        $db->statement('grant alter on SCHEMA::my_schema to my_user');
        $db->statement("alter user my_user with default_schema = my_schema execute as user='my_user'");

        config([
            'database.connections.'.$connection.'.username' => 'my_user',
            'database.connections.'.$connection.'.password' => 'Passw0rd',
        ]);

        $this->assertEquals('my_schema', $db->scalar('select schema_name()'));

        $schema->create('table', function (Blueprint $table) {
            $table->id();
        });

        $this->assertTrue($schema->hasTable('table'));
        $this->assertTrue($schema->hasTable('my_schema.table'));
        $this->assertFalse($schema->hasTable('dbo.table'));
    }

    public static function connectionProvider(): array
    {
        return [
            'without prefix' => ['without-prefix'],
            'with prefix' => ['with-prefix'],
        ];
    }
}
