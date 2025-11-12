<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
use Illuminate\Database\Schema\SqlServerBuilder;
use Illuminate\Tests\Database\Fixtures\Enums\Foo;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSqlServerSchemaGrammarTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add "id" int not null identity primary key',
            'alter table "users" add "email" nvarchar(255) not null',
        ], $statements);

        $conn = $this->getConnection(prefix: 'prefix_');
        $blueprint = new Blueprint($conn, 'users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "prefix_users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testCreateTemporaryTable()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getTablePrefix')->andReturn('');
        $blueprint = new Blueprint($connection, 'users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "#users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testCreateTemporaryTableWithPrefix()
    {
        $connection = $this->getConnection(prefix: 'prefix_');
        $blueprint = new Blueprint($connection, 'users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "#prefix_users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->drop();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "users"', $statements[0]);

        $conn = $this->getConnection(prefix: 'prefix_');
        $blueprint = new Blueprint($conn, 'users');
        $blueprint->drop();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "prefix_users"', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('if object_id(N\'"users"\', \'U\') is not null drop table "users"', $statements[0]);

        $conn = $this->getConnection(prefix: 'prefix_');
        $blueprint = new Blueprint($conn, 'users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('if object_id(N\'"prefix_users"\', \'U\') is not null drop table "prefix_users"', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo", "bar"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo", "bar"', $statements[0]);
    }

    public function testDropColumnDropsCreatesSqlToDropDefaultConstraints()
    {
        $blueprint = new Blueprint($this->getConnection(), 'foo');
        $blueprint->dropColumn('bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame("DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE \"foo\" DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID(N'\"foo\"') AND [name] in ('bar') AND [default_object_id] <> 0;EXEC(@sql);alter table \"foo\" drop column \"bar\"", $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropPrimary('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "geo_coordinates_spatialindex" on "geo"', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropConstrainedForeignId()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropConstrainedForeignId('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('alter table "users" drop constraint "users_foo_foreign"', $statements[0]);
        $this->assertSame('DECLARE @sql NVARCHAR(MAX) = \'\';SELECT @sql += \'ALTER TABLE "users" DROP CONSTRAINT \' + OBJECT_NAME([default_object_id]) + \';\' FROM sys.columns WHERE [object_id] = OBJECT_ID(N\'"users"\') AND [name] in (\'foo\') AND [default_object_id] <> 0;EXEC(@sql);alter table "users" drop column "foo"', $statements[1]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropMorphs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('drop index "photos_imageable_type_imageable_id_index" on "photos"', $statements[0]);
        $this->assertStringContainsString('alter table "photos" drop column "imageable_type", "imageable_id"', $statements[1]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('sp_rename N\'"users"\', "foo"', $statements[0]);
    }

    public function testRenameIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->renameIndex('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('sp_rename N\'"users"."foo"\', "bar", N\'INDEX\'', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "bar" primary key ("foo")', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create unique index "bar" on "users" ("foo")', $statements[0]);
    }

    public function testAddingUniqueKeyOnline()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->unique('foo', 'bar')->online();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create unique index "bar" on "users" ("foo") with (online = on)', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" ("foo", "bar")', $statements[0]);
    }

    public function testAddingIndexOnline()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz')->online();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" ("foo", "bar") with (online = on)', $statements[0]);
    }

    public function testAddingSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[0]);
    }

    public function testAddingFluentSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point')->spatialIndex();
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[1]);
    }

    public function testAddingRawIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->rawIndex('(function(column))', 'raw_index');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "raw_index" on "users" ((function(column)))', $statements[0]);
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" int not null identity primary key', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" smallint not null identity primary key', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" int not null identity primary key', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" bigint not null identity primary key', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingForeignID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $foreignId = $blueprint->foreignId('foo');
        $blueprint->foreignId('company_id')->constrained();
        $blueprint->foreignId('laravel_idea_id')->constrained();
        $blueprint->foreignId('team_id')->references('id')->on('teams');
        $blueprint->foreignId('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql();

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table "users" add "foo" bigint not null',
            'alter table "users" add "company_id" bigint not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add "laravel_idea_id" bigint not null',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add "team_id" bigint not null',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add "team_column_id" bigint not null',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingForeignIdSpecifyingIndexNameInConstraint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreignId('company_id')->constrained(indexName: 'my_index');
        $statements = $blueprint->toSql();
        $this->assertSame([
            'alter table "users" add "company_id" bigint not null',
            'alter table "users" add constraint "my_index" foreign key ("company_id") references "companies" ("id")',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(255) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(100) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null identity primary key', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null identity primary key', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" tinyint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" tinyint not null identity primary key', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" smallint not null identity primary key', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->float('foo', 5);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" float(5) not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->double('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" double precision not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bit not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->enum('role', ['member', 'admin']);
        $blueprint->enum('status', Foo::cases());
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('alter table "users" add "role" nvarchar(255) check ("role" in (N\'member\', N\'admin\')) not null', $statements[0]);
        $this->assertSame('alter table "users" add "status" nvarchar(255) check ("status" in (N\'bar\')) not null', $statements[1]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" date not null', $statements[0]);
    }

    public function testAddingDateWithDefaultCurrent()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->date('foo')->useCurrent();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" date not null default CAST(GETDATE() AS DATE)', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "birth_year" int not null', $statements[0]);
    }

    public function testAddingYearWithDefaultCurrent()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->year('birth_year')->useCurrent();
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "birth_year" int not null default CAST(YEAR(GETDATE()) AS INTEGER)', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" datetimeoffset not null', $statements[0]);
    }

    public function testAddingDateTimeTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('foo', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimestamp()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingTimestampTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetimeoffset not null', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTimestamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add "created_at" datetime null',
            'alter table "users" add "updated_at" datetime null',
        ], $statements);
    }

    public function testAddingTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add "created_at" datetimeoffset null',
            'alter table "users" add "updated_at" datetimeoffset null',
        ], $statements);
    }

    public function testAddingRememberToken()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "remember_token" nvarchar(100) null', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" varbinary(max) not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" uniqueidentifier not null', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "uuid" uniqueidentifier not null', $statements[0]);
    }

    public function testAddingForeignUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $foreignId = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql();

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table "users" add "foo" uniqueidentifier not null',
            'alter table "users" add "company_id" uniqueidentifier not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add "laravel_idea_id" uniqueidentifier not null',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add "team_id" uniqueidentifier not null',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add "team_column_id" uniqueidentifier not null',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(45) not null', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "ip_address" nvarchar(45) not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(17) not null', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "mac_address" nvarchar(17) not null', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geometry not null', $statements[0]);
    }

    public function testAddingGeography()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geography('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'products');
        $blueprint->integer('price');
        $blueprint->computed('discounted_virtual', 'price - 5');
        $blueprint->computed('discounted_stored', 'price - 5')->persisted();
        $statements = $blueprint->toSql();
        $this->assertCount(3, $statements);
        $this->assertSame([
            'alter table "products" add "price" int not null',
            'alter table "products" add "discounted_virtual" as (price - 5)',
            'alter table "products" add "discounted_stored" as (price - 5) persisted',
        ], $statements);

        $blueprint = new Blueprint($this->getConnection(), 'products');
        $blueprint->integer('price');
        $blueprint->computed('discounted_virtual', new Expression('price - 5'));
        $blueprint->computed('discounted_stored', new Expression('price - 5'))->persisted();
        $statements = $blueprint->toSql();
        $this->assertCount(3, $statements);
        $this->assertSame([
            'alter table "products" add "price" int not null',
            'alter table "products" add "discounted_virtual" as (price - 5)',
            'alter table "products" add "discounted_stored" as (price - 5) persisted',
        ], $statements);
    }

    public function testGrammarsAreMacroable()
    {
        // compileReplace macro.
        $this->getGrammar()::macro('compileReplace', function () {
            return true;
        });

        $c = $this->getGrammar()::compileReplace();

        $this->assertTrue($c);
    }

    public function testQuoteString()
    {
        $this->assertSame("N'中文測試'", $this->getGrammar()->quoteString('中文測試'));
    }

    public function testQuoteStringOnArray()
    {
        $this->assertSame("N'中文', N'測試'", $this->getGrammar()->quoteString(['中文', '測試']));
    }

    public function testCreateDatabase()
    {
        $statement = $this->getGrammar()->compileCreateDatabase('my_database_a');

        $this->assertSame(
            'create database "my_database_a"',
            $statement
        );

        $statement = $this->getGrammar()->compileCreateDatabase('my_database_b');

        $this->assertSame(
            'create database "my_database_b"',
            $statement
        );
    }

    public function testDropDatabaseIfExists()
    {
        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_a');

        $this->assertSame(
            'drop database if exists "my_database_a"',
            $statement
        );

        $statement = $this->getGrammar()->compileDropDatabaseIfExists('my_database_b');

        $this->assertSame(
            'drop database if exists "my_database_b"',
            $statement
        );
    }

    protected function getConnection(
        ?SqlServerGrammar $grammar = null,
        ?SqlServerBuilder $builder = null,
        string $prefix = ''
    ) {
        $connection = m::mock(Connection::class)
            ->shouldReceive('getTablePrefix')->andReturn($prefix)
            ->shouldReceive('getConfig')->with('prefix_indexes')->andReturn(null)
            ->getMock();

        $grammar ??= $this->getGrammar($connection);
        $builder ??= $this->getBuilder();

        return $connection
            ->shouldReceive('getSchemaGrammar')->andReturn($grammar)
            ->shouldReceive('getSchemaBuilder')->andReturn($builder)
            ->getMock();
    }

    public function getGrammar(?Connection $connection = null)
    {
        return new SqlServerGrammar($connection ?? $this->getConnection());
    }

    public function getBuilder()
    {
        return mock(SqlServerBuilder::class);
    }
}
