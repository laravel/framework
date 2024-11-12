<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\DmBuilder;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\DmGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseDmSchemaGrammarTest extends TestCase
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
        $this->assertSame('create table "users" ("id" int identity primary key not null, "email" varchar(255) not null)', $statements[0]);

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $blueprint = new Blueprint($conn, 'users');
        $blueprint->increments('id');
        $blueprint->string('email');

        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column("id" int identity primary key not null)',
            'alter table "users" add column("email" varchar(255) not null)',
        ], $statements);

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $blueprint = new Blueprint($conn, 'users');
        $blueprint->create();
        $blueprint->uuid('id')->primary();

        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame([
            'create table "users" ("id" char(36) not null)',
            'alter table "users" add constraint "users_id_primary" primary key ("id")',
        ], $statements);
    }

    public function testCreateTableAndCommentColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email')->comment('my first comment');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("id" int identity primary key not null, "email" varchar(255) not null comment \'my first comment\')', $statements[0]);
    }

    public function testCreateTemporaryTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create temporary table "users" ("id" int identity primary key not null, "email" varchar(255) not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->drop();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "users"', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop table if exists "users"', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('BEGIN FOR c IN (select \'foo\' as COL) LOOP EXECUTE IMMEDIATE (\'alter table "users" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('BEGIN FOR c IN (select \'foo\' as COL union select \'bar\') LOOP EXECUTE IMMEDIATE (\'alter table "users" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('BEGIN FOR c IN (select \'foo\' as COL union select \'bar\') LOOP EXECUTE IMMEDIATE (\'alter table "users" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[0]);
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
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo"', $statements[0]);
    }

    public function testDropSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "geo_coordinates_spatialindex"', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('BEGIN FOR c IN (select \'created_at\' as COL union select \'updated_at\') LOOP EXECUTE IMMEDIATE (\'alter table "users" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('BEGIN FOR c IN (select \'created_at\' as COL union select \'updated_at\') LOOP EXECUTE IMMEDIATE (\'alter table "users" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[0]);
    }

    public function testDropMorphs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        // $this->assertSame('drop index "photos_imageable_type_imageable_id_index"', $statements[0]);
        $this->assertSame('BEGIN FOR c IN (select \'imageable_type\' as COL union select \'imageable_id\') LOOP EXECUTE IMMEDIATE (\'alter table "photos" drop "\' || c.COL || \'"\'); END LOOP; END;', $statements[1]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" rename to "foo"', $statements[0]);
    }

    public function testRenameIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->renameIndex('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter index "foo" rename to "bar"', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->primary('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_foo_primary" primary key ("foo")', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "bar" unique ("foo")', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" ("foo", "bar")', $statements[0]);
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
        $this->assertSame('alter table "users" add column("id" int identity primary key not null)', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("id" smallint identity primary key not null)', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("id" int identity primary key not null)', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("id" bigint identity primary key not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" bigint identity primary key not null)', $statements[0]);
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
            'alter table "users" add column("foo" bigint not null)',
            'alter table "users" add column("company_id" bigint not null)',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add column("laravel_idea_id" bigint not null)',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add column("team_id" bigint not null)',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add column("team_column_id" bigint not null)',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingForeignIdSpecifyingIndexNameInConstraint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreignId('company_id')->constrained(indexName: 'my_index');
        $statements = $blueprint->toSql();
        $this->assertSame([
            'alter table "users" add column("company_id" bigint not null)',
            'alter table "users" add constraint "my_index" foreign key ("company_id") references "companies" ("id")',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("id" bigint identity primary key not null)', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(255) not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(100) not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(100) null default \'bar\')', $statements[0]);
    }

    public function testAddingStringWithoutLengthLimit()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(255) not null)', $statements[0]);

        Builder::$defaultStringLength = null;

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        try {
            $this->assertCount(1, $statements);
            $this->assertSame('alter table "users" add column("foo" varchar not null)', $statements[0]);
        } finally {
            Builder::$defaultStringLength = 255;
        }
    }

    public function testAddingCharWithoutLengthLimit()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->char('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" char(255) not null)', $statements[0]);

        Builder::$defaultStringLength = null;

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->char('foo');
        $statements = $blueprint->toSql();

        try {
            $this->assertCount(1, $statements);
            $this->assertSame('alter table "users" add column("foo" char not null)', $statements[0]);
        } finally {
            Builder::$defaultStringLength = 255;
        }
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" text not null)', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" bigint not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" bigint identity primary key not null)', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" int not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" int identity primary key not null)', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" int not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" int identity primary key not null)', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" tinyint not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" tinyint identity primary key not null)', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" smallint not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" smallint identity primary key not null)', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->float('foo', 5);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" float(5) not null)', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->double('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" double not null)', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" decimal(5, 2) not null)', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" bit not null)', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("role" varchar(255) check ("role" in (\'member\', \'admin\')) not null)', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" date not null)', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("birth_year" int not null)', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" clob not null)', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" clob not null)', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime not null)', $statements[0]);
    }

    public function testAddingDateTimeWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime(1) not null)', $statements[0]);
    }

    public function testAddingDateTimeWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime not null)', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime with time zone not null)', $statements[0]);
    }

    public function testAddingDateTimeTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime(1) with time zone not null)', $statements[0]);
    }

    public function testAddingDateTimeTzWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" datetime with time zone not null)', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time not null)', $statements[0]);
    }

    public function testAddingTimeWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time(1) not null)', $statements[0]);
    }

    public function testAddingTimeWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time not null)', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time with time zone not null)', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time(1) with time zone not null)', $statements[0]);
    }

    public function testAddingTimeTzWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" time with time zone not null)', $statements[0]);
    }

    public function testAddingTimestamp()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp not null)', $statements[0]);
    }

    public function testAddingTimestampWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp(1) not null)', $statements[0]);
    }

    public function testAddingTimestampWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp not null)', $statements[0]);
    }

    public function testAddingTimestampTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp with time zone not null)', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp(1) with time zone not null)', $statements[0]);
    }

    public function testAddingTimestampTzWithNullPrecision()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('created_at', null);
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("created_at" timestamp with time zone not null)', $statements[0]);
    }

    public function testAddingTimestamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column("created_at" timestamp null)',
            'alter table "users" add column("updated_at" timestamp null)',
        ], $statements);
    }

    public function testAddingTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column("created_at" timestamp with time zone null)',
            'alter table "users" add column("updated_at" timestamp with time zone null)',
        ], $statements);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" blob not null)', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" char(36) not null)', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("uuid" char(36) not null)', $statements[0]);
    }

    public function testAddingForeignUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $foreignUuid = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql();

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignUuid);
        $this->assertSame([
            'alter table "users" add column("foo" char(36) not null)',
            'alter table "users" add column("company_id" char(36) not null)',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add column("laravel_idea_id" char(36) not null)',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add column("team_id" char(36) not null)',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add column("team_column_id" char(36) not null)',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->integer('foo');
        $blueprint->integer('bar')->virtualAs('"foo" - 5');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("foo" int not null, "bar" int as ("foo" - 5))', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->integer('foo');
        $blueprint->integer('bar')->virtualAs('"foo" - 5')->nullable(false);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("foo" int not null, "bar" int not null as ("foo" - 5))', $statements[0]);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(45) not null)', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("ip_address" varchar(45) not null)', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("foo" varchar(17) not null)', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column("mac_address" varchar(17) not null)', $statements[0]);
    }

    public function testCompileForeign()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry not null)', $statements[0]);
    }

    public function testAddingGeography()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geography('coordinates', 'pointzm', 4269);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geography CHECK(type = pointzm) CHECK(srid = 4269) not null)', $statements[0]);
    }

    public function testAddingPoint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = point)  not null)', $statements[0]);
    }

    public function testAddingPointWithSrid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point', 4269);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = point) CHECK(srid = 4269) not null)', $statements[0]);
    }

    public function testAddingLineString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'linestring');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = linestring)  not null)', $statements[0]);
    }

    public function testAddingPolygon()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'polygon');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = polygon)  not null)', $statements[0]);
    }

    public function testAddingGeometryCollection()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'geometrycollection');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = geometrycollection)  not null)', $statements[0]);
    }

    public function testAddingMultiPoint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multipoint');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = multipoint)  not null)', $statements[0]);
    }

    public function testAddingMultiLineString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multilinestring');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = multilinestring)  not null)', $statements[0]);
    }

    public function testAddingMultiPolygon()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multipolygon');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column("coordinates" SYSGEO2.ST_Geometry CHECK(type = multipolygon)  not null)', $statements[0]);
    }

    public function testDropAllTablesEscapesTableNames()
    {
        $statement = $this->getGrammar()->compileDropAllTables(['alpha', 'beta', 'gamma']);

        $this->assertSame('BEGIN FOR c IN (SELECT table_name FROM user_tables where table_name not like \'%HISTOGRAMS_TABLE\') LOOP EXECUTE IMMEDIATE (\'DROP TABLE "\' || c.table_name || \'" CASCADE\'); END LOOP; END;', $statement);
    }

    public function testDropAllViewsEscapesTableNames()
    {
        $statement = $this->getGrammar()->compileDropAllViews(['alpha', 'beta', 'gamma']);

        $this->assertSame('BEGIN FOR c IN (select \'"alpha"\' as view_name union select \'"beta"\' as view_name union select \'"gamma"\' as view_name) LOOP EXECUTE IMMEDIATE (\'DROP VIEW "\' || c.view_name || \'" CASCADE\'); END LOOP; END;', $statement);
    }

    protected function getConnection(
        ?DmGrammar $grammar = null,
        ?DmBuilder $builder = null
    ) {
        $grammar ??= $this->getGrammar();
        $builder ??= $this->getBuilder();

        return m::mock(Connection::class)
            ->shouldReceive('getSchemaGrammar')->andReturn($grammar)
            ->shouldReceive('getSchemaBuilder')->andReturn($builder)
            ->getMock();
    }

    public function getGrammar()
    {
        return new DmGrammar;
    }

    public function getBuilder()
    {
        return mock(DmBuilder::class);
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
}
