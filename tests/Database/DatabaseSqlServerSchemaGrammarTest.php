<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSqlServerSchemaGrammarTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function testBasicCreateTable(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('create table "users" ("id" int identity primary key not null, "email" nvarchar(255) not null)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "id" int identity primary key not null, "email" nvarchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertEquals('create table "prefix_users" ("id" int identity primary key not null, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testCreateTemporaryTable(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->temporary();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('create table "#users" ("id" int identity primary key not null, "email" nvarchar(255) not null)', $statements[0]);
    }

    public function testDropTable(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('drop table "users"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertEquals('drop table "prefix_users"', $statements[0]);
    }

    public function testDropTableIfExists(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('if exists (select * from INFORMATION_SCHEMA.TABLES where TABLE_NAME = \'users\') drop table "users"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar()->setTablePrefix('prefix_'));

        $this->assertCount(1, $statements);
        $this->assertEquals('if exists (select * from INFORMATION_SCHEMA.TABLES where TABLE_NAME = \'prefix_users\') drop table "prefix_users"', $statements[0]);
    }

    public function testDropColumn(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop column "foo", "bar"', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop column "foo", "bar"', $statements[0]);
    }

    public function testDropPrimary(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropPrimary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropUnique(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropIndex(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('drop index "foo" on "users"', $statements[0]);
    }

    public function testDropSpatialIndex(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('drop index "geo_coordinates_spatialindex" on "geo"', $statements[0]);
    }

    public function testDropForeign(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropTimestamps(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropTimestampsTz(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" drop column "created_at", "updated_at"', $statements[0]);
    }

    public function testDropMorphs(): void
    {
        $blueprint = new Blueprint('photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertEquals('drop index "photos_imageable_type_imageable_id_index" on "photos"', $statements[0]);
        $this->assertEquals('alter table "photos" drop column "imageable_type", "imageable_id"', $statements[1]);
    }

    public function testRenameTable(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('sp_rename "users", "foo"', $statements[0]);
    }

    public function testAddingPrimaryKey(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->primary('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add constraint "bar" primary key ("foo")', $statements[0]);
    }

    public function testAddingUniqueKey(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('create unique index "bar" on "users" ("foo")', $statements[0]);
    }

    public function testAddingIndex(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('create index "baz" on "users" ("foo", "bar")', $statements[0]);
    }

    public function testAddingSpatialIndex(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[0]);
    }

    public function testAddingFluentSpatialIndex(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates')->spatialIndex();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $this->assertEquals('create spatial index "geo_coordinates_spatialindex" on "geo" ("coordinates")', $statements[1]);
    }

    public function testAddingIncrementingID(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "id" int identity primary key not null', $statements[0]);
    }

    public function testAddingSmallIncrementingID(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "id" smallint identity primary key not null', $statements[0]);
    }

    public function testAddingMediumIncrementingID(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "id" int identity primary key not null', $statements[0]);
    }

    public function testAddingBigIncrementingID(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "id" bigint identity primary key not null', $statements[0]);
    }

    public function testAddingString(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(255) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(100) not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingText(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingBigInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" bigint identity primary key not null', $statements[0]);
    }

    public function testAddingInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" int identity primary key not null', $statements[0]);
    }

    public function testAddingMediumInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" int not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" int identity primary key not null', $statements[0]);
    }

    public function testAddingTinyInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" tinyint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" tinyint identity primary key not null', $statements[0]);
    }

    public function testAddingSmallInteger(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" smallint identity primary key not null', $statements[0]);
    }

    public function testAddingFloat(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" float not null', $statements[0]);
    }

    public function testAddingDouble(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" float not null', $statements[0]);
    }

    public function testAddingDecimal(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" bit not null', $statements[0]);
    }

    public function testAddingEnum(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "role" nvarchar(255) check ("role" in (\'member\', \'admin\')) not null', $statements[0]);
    }

    public function testAddingJson(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingJsonb(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(max) not null', $statements[0]);
    }

    public function testAddingDate(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" date not null', $statements[0]);
    }

    public function testAddingYear(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "birth_year" int not null', $statements[0]);
    }

    public function testAddingDateTime(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingDateTimeTz(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" datetimeoffset not null', $statements[0]);
    }

    public function testAddingDateTimeTzWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('foo', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTime(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimeTz(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" time(1) not null', $statements[0]);
    }

    public function testAddingTimestamp(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetime2(1) not null', $statements[0]);
    }

    public function testAddingTimestampTz(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetimeoffset(0) not null', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetimeoffset(1) not null', $statements[0]);
    }

    public function testAddingTimestamps(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetime null, "updated_at" datetime null', $statements[0]);
    }

    public function testAddingTimestampsTz(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "created_at" datetimeoffset(0) null, "updated_at" datetimeoffset(0) null', $statements[0]);
    }

    public function testAddingRememberToken(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "remember_token" nvarchar(100) null', $statements[0]);
    }

    public function testAddingBinary(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" varbinary(max) not null', $statements[0]);
    }

    public function testAddingUuid(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" uniqueidentifier not null', $statements[0]);
    }

    public function testAddingIpAddress(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(45) not null', $statements[0]);
    }

    public function testAddingMacAddress(): void
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "users" add "foo" nvarchar(17) not null', $statements[0]);
    }

    public function testAddingGeometry(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingPoint(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingLineString(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->linestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingPolygon(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->polygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingGeometryCollection(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometrycollection('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiPoint(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipoint('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiLineString(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multilinestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    public function testAddingMultiPolygon(): void
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipolygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertEquals('alter table "geo" add "coordinates" geography not null', $statements[0]);
    }

    protected function getConnection()
    {
        return m::mock('Illuminate\Database\Connection');
    }

    public function getGrammar()
    {
        return new \Illuminate\Database\Schema\Grammars\SqlServerGrammar;
    }
}
