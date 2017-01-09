<?php

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;

class DatabasePostgresSchemaGrammarTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testBasicCreateTable()
    {
        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('char_default_length', 255)->andReturn(255);

        $blueprint = new Blueprint($conn, 'users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create table "users" ("id" serial primary key not null, "email" varchar(255) not null)', $statements[0]);

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('char_default_length', 255)->andReturn(255);

        $blueprint = new Blueprint($conn, 'users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "id" serial primary key not null, add column "email" varchar(255) not null', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table "users"', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop table if exists "users"', $statements[0]);
    }

    public function testDropColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop column "foo", drop column "bar"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop column "foo", drop column "bar"', $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropPrimary();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop constraint "users_pkey"', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('drop index "foo"', $statements[0]);
    }

    public function testDropForeign()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropForeign('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop constraint "foo"', $statements[0]);
    }

    public function testDropTimestamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestamps();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" rename to "foo"', $statements[0]);
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->primary('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add primary key ("foo")', $statements[0]);
    }

    public function testAddingUniqueKey()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->unique('foo', 'bar');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add constraint "bar" unique ("foo")', $statements[0]);
    }

    public function testAddingIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create index "baz" on "users" ("foo", "bar")', $statements[0]);
    }

    public function testAddingIndexWithAlgorithm()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz', 'hash');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('create index "baz" on "users" using hash ("foo", "bar")', $statements[0]);
    }

    public function testAddingIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('id');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "id" serial primary key not null', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "id" smallserial primary key not null', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "id" serial primary key not null', $statements[0]);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "id" bigserial primary key not null', $statements[0]);
    }

    public function testAddingString()
    {
        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->once()->with('char_default_length', 255)->andReturn(255);

        $blueprint = new Blueprint($conn, 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" varchar(100) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" varchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" bigserial primary key not null', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" serial primary key not null', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" serial primary key not null', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" smallserial primary key not null', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" smallserial primary key not null', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" double precision not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->double('foo', 15, 8);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" double precision not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" boolean not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->enum('foo', ['bar', 'baz']);
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" varchar(255) check ("foo" in (\'bar\', \'baz\')) not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" date not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" json not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" jsonb not null', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTime('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" timestamp(0) without time zone not null', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dateTimeTz('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" timestamp(0) with time zone not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->time('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" time(0) without time zone not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timeTz('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" time(0) with time zone not null', $statements[0]);
    }

    public function testAddingTimeStamp()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamp('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" timestamp(0) without time zone not null', $statements[0]);
    }

    public function testAddingTimeStampTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampTz('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" timestamp(0) with time zone not null', $statements[0]);
    }

    public function testAddingTimeStamps()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "created_at" timestamp(0) without time zone null, add column "updated_at" timestamp(0) without time zone null', $statements[0]);
    }

    public function testAddingTimeStampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "created_at" timestamp(0) with time zone null, add column "updated_at" timestamp(0) with time zone null', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" bytea not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" uuid not null', $statements[0]);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" inet not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getGrammar());

        $this->assertEquals(1, count($statements));
        $this->assertEquals('alter table "users" add column "foo" macaddr not null', $statements[0]);
    }

    protected function getConnection()
    {
        return m::mock('Illuminate\Database\Connection');
    }

    public function getGrammar()
    {
        return new Illuminate\Database\Schema\Grammars\PostgresGrammar;
    }
}
