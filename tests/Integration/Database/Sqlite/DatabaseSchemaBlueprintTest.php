<?php

namespace Illuminate\Tests\Integration\Database\Sqlite;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\TestCase;
use RuntimeException;

#[RequiresDatabase('sqlite')]
class DatabaseSchemaBlueprintTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set([
            'database.default' => 'testing',
        ]);
    }

    public function testRenamingAndChangingColumnsWork()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name');
            $table->string('age');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->renameColumn('name', 'first_name');
            $table->integer('age')->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'alter table "users" rename column "name" to "first_name"',
            'create table "__temp__users" ("first_name" varchar not null, "age" integer not null)',
            'insert into "__temp__users" ("first_name", "age") select "first_name", "age" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testRenamingColumnsWorks()
    {
        $schema = DB::connection()->getSchemaBuilder();

        $schema->create('test', function (Blueprint $table) {
            $table->string('foo');
            $table->string('baz');
        });

        $schema->table('test', function (Blueprint $table) {
            $table->renameColumn('foo', 'bar');
            $table->renameColumn('baz', 'qux');
        });

        $this->assertFalse($schema->hasColumn('test', 'foo'));
        $this->assertFalse($schema->hasColumn('test', 'baz'));
        $this->assertTrue($schema->hasColumns('test', ['bar', 'qux']));
    }

    public function testNativeColumnModifyingOnMySql()
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();

        $blueprint = new Blueprint('users', function ($table) {
            $table->double('amount')->nullable()->invisible()->after('name')->change();
            $table->timestamp('added_at', 4)->nullable(false)->useCurrent()->useCurrentOnUpdate()->change();
            $table->enum('difficulty', ['easy', 'hard'])->default('easy')->charset('utf8mb4')->collation('unicode')->change();
            $table->geometry('positions', 'multipolygon', 1234)->storedAs('expression')->change();
            $table->string('old_name', 50)->renameTo('new_name')->change();
            $table->bigIncrements('id')->first()->from(10)->comment('my comment')->change();
        });

        $this->assertEquals([
            'alter table `users` modify `amount` double null invisible after `name`',
            'alter table `users` modify `added_at` timestamp(4) not null default CURRENT_TIMESTAMP(4) on update CURRENT_TIMESTAMP(4)',
            "alter table `users` modify `difficulty` enum('easy', 'hard') character set utf8mb4 collate 'unicode' not null default 'easy'",
            'alter table `users` modify `positions` multipolygon srid 1234 as (expression) stored',
            'alter table `users` change `old_name` `new_name` varchar(50) not null',
            "alter table `users` modify `id` bigint unsigned not null auto_increment comment 'my comment' first",
            'alter table `users` auto_increment = 10',
        ], $blueprint->toSql($connection, new MySqlGrammar));
    }

    public function testNativeColumnModifyingOnPostgreSql()
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();

        $blueprint = new Blueprint('users', function ($table) {
            $table->integer('code')->autoIncrement()->from(10)->comment('my comment')->change();
        });

        $this->assertEquals([
            'alter table "users" '
            .'alter column "code" type integer, '
            .'alter column "code" set not null',
            'alter sequence users_code_seq restart with 10',
            'comment on column "users"."code" is \'my comment\'',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->char('name', 40)->nullable()->default('easy')->collation('unicode')->change();
        });

        $this->assertEquals([
            'alter table "users" '
            .'alter column "name" type char(40) collate "unicode", '
            .'alter column "name" drop not null, '
            .'alter column "name" set default \'easy\', '
            .'alter column "name" drop identity if exists',
            'comment on column "users"."name" is NULL',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->integer('foo')->generatedAs('expression')->always()->change();
        });

        $this->assertEquals([
            'alter table "users" '
            .'alter column "foo" type integer, '
            .'alter column "foo" set not null, '
            .'alter column "foo" drop default, '
            .'alter column "foo" drop identity if exists, '
            .'alter column "foo" add  generated always as identity (expression)',
            'comment on column "users"."foo" is NULL',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->geometry('foo', 'point', 1234)->change();
        });

        $this->assertEquals([
            'alter table "users" '
            .'alter column "foo" type geometry(point,1234), '
            .'alter column "foo" set not null, '
            .'alter column "foo" drop default, '
            .'alter column "foo" drop identity if exists',
            'comment on column "users"."foo" is NULL',
        ], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->timestamp('added_at', 2)->useCurrent()->storedAs(null)->change();
        });

        $this->assertEquals([
            'alter table "users" '
            .'alter column "added_at" type timestamp(2) without time zone, '
            .'alter column "added_at" set not null, '
            .'alter column "added_at" set default CURRENT_TIMESTAMP, '
            .'alter column "added_at" drop expression if exists, '
            .'alter column "added_at" drop identity if exists',
            'comment on column "users"."added_at" is NULL',
        ], $blueprint->toSql($connection, new PostgresGrammar));
    }

    public function testNativeColumnModifyingOnSqlServer()
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();

        $blueprint = new Blueprint('users', function ($table) {
            $table->timestamp('added_at', 4)->nullable(false)->useCurrent()->change();
        });

        $this->assertEquals([
            "DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"users\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"users\"') AND [name] in ('added_at') AND [default_object_id] <> 0;EXEC(@sql)",
            'alter table "users" alter column "added_at" datetime2(4) not null',
            'alter table "users" add default CURRENT_TIMESTAMP for "added_at"',
        ], $blueprint->toSql($connection, new SqlServerGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->char('name', 40)->nullable()->default('easy')->collation('unicode')->change();
        });

        $this->assertEquals([
            "DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"users\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"users\"') AND [name] in ('name') AND [default_object_id] <> 0;EXEC(@sql)",
            'alter table "users" alter column "name" nchar(40) collate unicode null',
            'alter table "users" add default \'easy\' for "name"',
        ], $blueprint->toSql($connection, new SqlServerGrammar));

        $blueprint = new Blueprint('users', function ($table) {
            $table->integer('foo')->change();
        });

        $this->assertEquals([
            "DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"users\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"users\"') AND [name] in ('foo') AND [default_object_id] <> 0;EXEC(@sql)",
            'alter table "users" alter column "foo" int not null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testChangingColumnWithCollationWorks()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('age');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->integer('age')->collation('RTRIM')->change();
        });

        $blueprint2 = new Blueprint('users', function ($table) {
            $table->integer('age')->collation('NOCASE')->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("age" integer not null collate \'RTRIM\')',
            'insert into "__temp__users" ("age") select "age" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
        ];

        $this->assertEquals($expected, $queries);

        $queries = $blueprint2->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("age" integer not null collate \'NOCASE\')',
            'insert into "__temp__users" ("age") select "age" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testChangingCharColumnsWork()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->char('name', 50)->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("name" varchar not null)',
            'insert into "__temp__users" ("name") select "name" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testChangingPrimaryAutoincrementColumnsToNonAutoincrementColumnsWork()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->increments('id');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->binary('id')->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("id" blob not null, primary key ("id"))',
            'insert into "__temp__users" ("id") select "id" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testChangingDoubleColumnsWork()
    {
        DB::connection()->getSchemaBuilder()->create('products', function ($table) {
            $table->integer('price');
        });

        $blueprint = new Blueprint('products', function ($table) {
            $table->double('price')->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__products" ("price" double not null)',
            'insert into "__temp__products" ("price") select "price" from "products"',
            'drop table "products"',
            'alter table "__temp__products" rename to "products"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testChangingColumnsWithDefaultWorks()
    {
        DB::connection()->getSchemaBuilder()->create('products', function (Blueprint $table) {
            $table->integer('changed_col');
            $table->timestamp('timestamp_col')->useCurrent();
            $table->integer('integer_col')->default(123);
            $table->string('string_col')->default('value');
        });

        $blueprint = new Blueprint('products', function ($table) {
            $table->text('changed_col')->change();
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__products" ("changed_col" text not null, "timestamp_col" datetime not null default (CURRENT_TIMESTAMP), "integer_col" integer not null default (\'123\'), "string_col" varchar not null default (\'value\'))',
            'insert into "__temp__products" ("changed_col", "timestamp_col", "integer_col", "string_col") select "changed_col", "timestamp_col", "integer_col", "string_col" from "products"',
            'drop table "products"',
            'alter table "__temp__products" rename to "products"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testRenameIndexWorks()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name');
            $table->string('age');
        });

        DB::connection()->getSchemaBuilder()->table('users', function ($table) {
            $table->index(['name'], 'index1');
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->renameIndex('index1', 'index2');
        });

        $queries = $blueprint->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'drop index "index1"',
            'create index "index2" on "users" ("name")',
        ];

        $this->assertEquals($expected, $queries);

        $queries = $blueprint->toSql(DB::connection(), new SqlServerGrammar);

        $expected = [
            'sp_rename N\'"users"."index1"\', "index2", N\'INDEX\'',
        ];

        $this->assertEquals($expected, $queries);

        $queries = $blueprint->toSql(DB::connection(), new MySqlGrammar);

        $expected = [
            'alter table `users` rename index `index1` to `index2`',
        ];

        $this->assertEquals($expected, $queries);

        $queries = $blueprint->toSql(DB::connection(), new PostgresGrammar);

        $expected = [
            'alter index "index1" rename to "index2"',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAddUniqueIndexWithoutNameWorks()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name')->nullable();
        });

        $blueprintMySql = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique()->change();
        });

        $queries = $blueprintMySql->toSql(DB::connection(), new MySqlGrammar);

        $expected = [
            'alter table `users` modify `name` varchar(255) null',
            'alter table `users` add unique `users_name_unique`(`name`)',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintPostgres = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique()->change();
        });

        $queries = $blueprintPostgres->toSql(DB::connection(), new PostgresGrammar);

        $expected = [
            'alter table "users" alter column "name" type varchar(255), alter column "name" drop not null, alter column "name" drop default, alter column "name" drop identity if exists',
            'alter table "users" add constraint "users_name_unique" unique ("name")',
            'comment on column "users"."name" is NULL',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintSQLite = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique()->change();
        });

        $queries = $blueprintSQLite->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("name" varchar)',
            'insert into "__temp__users" ("name") select "name" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
            'create unique index "users_name_unique" on "users" ("name")',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintSqlServer = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique()->change();
        });

        $queries = $blueprintSqlServer->toSql(DB::connection(), new SqlServerGrammar);

        $expected = [
            "DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"users\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"users\"') AND [name] in ('name') AND [default_object_id] <> 0;EXEC(@sql)",
            'alter table "users" alter column "name" nvarchar(255) null',
            'create unique index "users_name_unique" on "users" ("name")',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAddUniqueIndexWithNameWorks()
    {
        DB::connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name')->nullable();
        });

        $blueprintMySql = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique('index1')->change();
        });

        $queries = $blueprintMySql->toSql(DB::connection(), new MySqlGrammar);

        $expected = [
            'alter table `users` modify `name` varchar(255) null',
            'alter table `users` add unique `index1`(`name`)',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintPostgres = new Blueprint('users', function ($table) {
            $table->unsignedInteger('name')->nullable()->unique('index1')->change();
        });

        $queries = $blueprintPostgres->toSql(DB::connection(), new PostgresGrammar);

        $expected = [
            'alter table "users" alter column "name" type integer, alter column "name" drop not null, alter column "name" drop default, alter column "name" drop identity if exists',
            'alter table "users" add constraint "index1" unique ("name")',
            'comment on column "users"."name" is NULL',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintSQLite = new Blueprint('users', function ($table) {
            $table->unsignedInteger('name')->nullable()->unique('index1')->change();
        });

        $queries = $blueprintSQLite->toSql(DB::connection(), new SQLiteGrammar);

        $expected = [
            'create table "__temp__users" ("name" integer)',
            'insert into "__temp__users" ("name") select "name" from "users"',
            'drop table "users"',
            'alter table "__temp__users" rename to "users"',
            'create unique index "index1" on "users" ("name")',
        ];

        $this->assertEquals($expected, $queries);

        $blueprintSqlServer = new Blueprint('users', function ($table) {
            $table->unsignedInteger('name')->nullable()->unique('index1')->change();
        });

        $queries = $blueprintSqlServer->toSql(DB::connection(), new SqlServerGrammar);

        $expected = [
            "DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"users\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"users\"') AND [name] in ('name') AND [default_object_id] <> 0;EXEC(@sql)",
            'alter table "users" alter column "name" int null',
            'create unique index "index1" on "users" ("name")',
        ];

        $this->assertEquals($expected, $queries);
    }

    public function testAddColumnNamedCreateWorks()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('create')->nullable();
        });

        $this->assertTrue(Schema::hasColumn('users', 'create'));
    }

    public function testDropIndexOnColumnChangeWorks()
    {
        $connection = DB::connection();

        $connection->getSchemaBuilder()->create('users', function ($table) {
            $table->string('name')->nullable();
        });

        $blueprint = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique(false)->change();
        });

        $this->assertContains(
            'alter table `users` drop index `users_name_unique`',
            $blueprint->toSql($connection, new MySqlGrammar)
        );

        $blueprint = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique(false)->change();
        });

        $this->assertContains(
            'alter table "users" drop constraint "users_name_unique"',
            $blueprint->toSql($connection, new PostgresGrammar)
        );

        $blueprint = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique(false)->change();
        });

        $this->assertContains(
            'drop index "users_name_unique"',
            $blueprint->toSql($connection, new SQLiteGrammar)
        );

        $blueprint = new Blueprint('users', function ($table) {
            $table->string('name')->nullable()->unique(false)->change();
        });

        $this->assertContains(
            'drop index "users_name_unique" on "users"',
            $blueprint->toSql($connection, new SqlServerGrammar)
        );
    }

    public function testItDoesNotSetPrecisionHigherThanSupportedWhenRenamingTimestamps()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->timestamp('created_at');
        });

        try {
            // this would only fail in mysql, postgres and sql server
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('created_at', 'new_created_at');
            });

            $this->addToAssertionCount(1); // it did not throw
        } catch (\Exception $e) {
            // Expecting something similar to:
            // Illuminate\Database\QueryException
            //   SQLSTATE[42000]: Syntax error or access violation: 1426 Too big precision 10 specified for 'my_timestamp'. Maximum is 6....
            $this->fail('test_it_does_not_set_precision_higher_than_supported_when_renaming_timestamps has failed. Error: '.$e->getMessage());
        }
    }

    public function testItEnsuresDroppingForeignKeyIsAvailable()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database driver does not support dropping foreign keys by name.');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('something');
        });
    }
}
