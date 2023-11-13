<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
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
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" int not null identity primary key, "email" nvarchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertSame('create table "prefix_users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testCreateTemporaryTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "#users" ("id" int not null identity primary key, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "users"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "prefix_users"', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('if exists (select * from sys.sysobjects where id = object_id(\'users\', \'U\')) drop table "users"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertSame('if exists (select * from sys.sysobjects where id = object_id(\'prefix_users\', \'U\')) drop table "prefix_users"', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo", "bar"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "foo", "bar"', $statements[0]);
    }

    public function testDropColumnDropsCreatesSqlToDropDefaultConstraints()
    {
        $blueprint = new Blueprint('foo');
        $blueprint->dropColumn('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("DECLARE @sql NVARCHAR(MAX) = '';SELECT @sql += 'ALTER TABLE [dbo].[foo] DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';' FROM sys.columns WHERE [object_id] = OBJECT_ID('[dbo].[foo]') AND [name] in ('bar') AND [default_object_id] <> 0;EXEC(@sql);alter table \"foo\" drop column \"bar\"", $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "geo_coordinates_spatialindex" on "geo"', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropConstrainedForeignId()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropConstrainedForeignId('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('alter table "users" drop constraint "users_foo_foreign"', $statements[0]);
        $this->assertSame('DECLARE @sql NVARCHAR(MAX) = \'\';SELECT @sql += \'ALTER TABLE [dbo].[users] DROP CONSTRAINT \' + OBJECT_NAME([default_object_id]) + \';\' FROM sys.columns WHERE [object_id] = OBJECT_ID(\'[dbo].[users]\') AND [name] in (\'foo\') AND [default_object_id] <> 0;EXEC(@sql);alter table "users" drop column "foo"', $statements[1]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertStringContainsString('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropMorphs()
    {
        $blueprint = new Blueprint('photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('drop index "photos_imageable_type_imageable_id_index" on "photos"', $statements[0]);
        $this->assertStringContainsString('alter table "photos" drop column "imageable_type", "imageable_id"', $statements[1]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('sp_rename "users", "foo"', $statements[0]);
    }

    public function testRenameIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->renameIndex('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('sp_rename N\'"users"."foo"\', "bar", N\'INDEX\'', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "bar" primary key ("foo")', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create unique index "bar" on "users" ("foo")', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" ("foo", "bar")', $statements[0]);
    }

    public function testAddingSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[0]);
    }

    public function testAddingFluentSpatialIndex()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates')->spatialIndex();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertSame('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[1]);
    }

    public function testAddingRawIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rawIndex('(function(column))', 'raw_index');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create index "raw_index" on "users" ((function(column)))', $statements[0]);
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" int not null identity primary key', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" smallint not null identity primary key', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" int not null identity primary key', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" bigint not null identity primary key', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingForeignID()
    {
        $blueprint = new Blueprint('users');
        $foreignId = $blueprint->foreignId('foo');
        $blueprint->foreignId('company_id')->constrained();
        $blueprint->foreignId('laravel_idea_id')->constrained();
        $blueprint->foreignId('team_id')->references('id')->on('teams');
        $blueprint->foreignId('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table "users" add "foo" bigint not null, "company_id" bigint not null, "laravel_idea_id" bigint not null, "team_id" bigint not null, "team_column_id" bigint not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingForeignIdSpecifyingIndexNameInConstraint()
    {
        $blueprint = new Blueprint('users');
        $blueprint->foreignId('company_id')->constrained(indexName: 'my_index');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertSame([
            'alter table "users" add "company_id" bigint not null',
            'alter table "users" add constraint "my_index" foreign key ("company_id") references "companies" ("id")',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "id" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(100) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bigint not null identity primary key', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null identity primary key', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" int not null identity primary key', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" tinyint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" tinyint not null identity primary key', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" smallint not null identity primary key', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" float not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" float not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" bit not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "role" nvarchar(255) check ("role" in (N\'member\', N\'admin\')) not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" date not null', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint('users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "birth_year" int not null', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" datetimeoffset not null', $statements[0]);
    }

    public function testAddingDateTimeTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimestamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingTimestampTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetimeoffset not null', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetime null, "updated_at" datetime null', $statements[0]);
    }

    public function testAddingTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "created_at" datetimeoffset null, "updated_at" datetimeoffset null', $statements[0]);
    }

    public function testAddingRememberToken()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "remember_token" nvarchar(100) null', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" varbinary(max) not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" uniqueidentifier not null', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "uuid" uniqueidentifier not null', $statements[0]);
    }

    public function testAddingForeignUuid()
    {
        $blueprint = new Blueprint('users');
        $foreignId = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignId);
        $this->assertSame([
            'alter table "users" add "foo" uniqueidentifier not null, "company_id" uniqueidentifier not null, "laravel_idea_id" uniqueidentifier not null, "team_id" uniqueidentifier not null, "team_column_id" uniqueidentifier not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(45) not null', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "ip_address" nvarchar(45) not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "foo" nvarchar(17) not null', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add "mac_address" nvarchar(17) not null', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->linestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->polygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingGeometryCollection()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometrycollection('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipoint('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multilinestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipolygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->computed('discounted_virtual', 'price - 5');
        $blueprint->computed('discounted_stored', 'price - 5')->persisted();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "products" add "price" int not null, "discounted_virtual" as (price - 5), "discounted_stored" as (price - 5) persisted', $statements[0]);

        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->computed('discounted_virtual', new Expression('price - 5'));
        $blueprint->computed('discounted_stored', new Expression('price - 5'))->persisted();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "products" add "price" int not null, "discounted_virtual" as (price - 5), "discounted_stored" as (price - 5) persisted', $statements[0]);
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
        $connection = $this->getConnection();

        $statement = $this->getGrammar()->compileCreateDatabase('my_database_a', $connection);

        $this->assertSame(
            'create database "my_database_a"',
            $statement
        );

        $statement = $this->getGrammar()->compileCreateDatabase('my_database_b', $connection);

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

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    public function getGrammar()
    {
        return new SqlServerGrammar;
    }
}
