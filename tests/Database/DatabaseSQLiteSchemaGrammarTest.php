<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DatabaseSQLiteSchemaGrammarTest extends TestCase
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
        $this->assertSame('create table "users" ("id" integer not null primary key autoincrement, "email" varchar not null)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $expected = [
            'alter table "users" add column "id" integer not null primary key autoincrement',
            'alter table "users" add column "email" varchar not null',
        ];
        $this->assertEquals($expected, $statements);
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
        $this->assertSame('create temporary table "users" ("id" integer not null primary key autoincrement, "email" varchar not null)', $statements[0]);
    }

    public function testDropTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->drop();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table "users"', $statements[0]);
    }

    public function testDropTableIfExists()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIfExists();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop table if exists "users"', $statements[0]);
    }

    public function testDropUnique()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo"', $statements[0]);
    }

    public function testDropIndex()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropIndex('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('drop index "foo"', $statements[0]);
    }

    public function testDropColumn()
    {
        $db = new Manager;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'prefix_',
        ]);

        $schema = $db->getConnection()->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table) {
            $table->string('email');
            $table->string('name');
        });

        $this->assertTrue($schema->hasTable('users'));
        $this->assertTrue($schema->hasColumn('users', 'name'));

        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        $this->assertFalse($schema->hasColumn('users', 'name'));
    }

    public function testDropSpatialIndex()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The database driver in use does not support spatial indexes.');

        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $blueprint->toSql($this->getConnection(), $this->getGrammar());
    }

    public function testRenameTable()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rename('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" rename to "foo"', $statements[0]);
    }

    public function testRenameIndex()
    {
        $db = new Manager;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'prefix_',
        ]);

        $schema = $db->getConnection()->getSchemaBuilder();

        $schema->create('users', function (Blueprint $table) {
            $table->string('name');
            $table->string('email');
        });

        $schema->table('users', function (Blueprint $table) {
            $table->index(['name', 'email'], 'index1');
        });

        $manager = $db->getConnection()->getDoctrineSchemaManager();
        $details = $manager->listTableDetails('prefix_users');
        $this->assertTrue($details->hasIndex('index1'));
        $this->assertFalse($details->hasIndex('index2'));

        $schema->table('users', function (Blueprint $table) {
            $table->renameIndex('index1', 'index2');
        });

        $details = $manager->listTableDetails('prefix_users');
        $this->assertFalse($details->hasIndex('index1'));
        $this->assertTrue($details->hasIndex('index2'));

        $this->assertEquals(['name', 'email'], $details->getIndex('index2')->getUnquotedColumns());
    }

    public function testAddingPrimaryKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('foo')->primary();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("foo" varchar not null, primary key ("foo"))', $statements[0]);
    }

    public function testAddingForeignKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('foo')->primary();
        $blueprint->string('order_id');
        $blueprint->foreign('order_id')->references('id')->on('orders');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("foo" varchar not null, "order_id" varchar not null, foreign key("order_id") references "orders"("id"), primary key ("foo"))', $statements[0]);
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The database driver in use does not support spatial indexes.');

        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $blueprint->toSql($this->getConnection(), $this->getGrammar());
    }

    public function testAddingFluentSpatialIndex()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The database driver in use does not support spatial indexes.');

        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates')->spatialIndex();
        $blueprint->toSql($this->getConnection(), $this->getGrammar());
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
        $this->assertSame('alter table "users" add column "id" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->id();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" integer not null primary key autoincrement', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
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
            'alter table "users" add column "foo" integer not null',
            'alter table "users" add column "company_id" integer not null',
            'alter table "users" add column "laravel_idea_id" integer not null',
            'alter table "users" add column "team_id" integer not null',
            'alter table "users" add column "team_column_id" integer not null',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint('users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar default \'bar\'', $statements[0]);
    }

    public function testAddingText()
    {
        $blueprint = new Blueprint('users');
        $blueprint->text('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null primary key autoincrement', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint('users');
        $blueprint->float('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" float not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint('users');
        $blueprint->double('foo', 15, 8);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" float not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint('users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" numeric not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint('users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" tinyint(1) not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint('users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "role" varchar check ("role" in (\'member\', \'admin\')) not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint('users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint('users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint('users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" date not null', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint('users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "birth_year" integer not null', $statements[0]);
    }

    public function testAddingDateTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTime('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingDateTimeTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dateTimeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTime()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->time('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time not null', $statements[0]);
    }

    public function testAddingTimeTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timeTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" time not null', $statements[0]);
    }

    public function testAddingTimestamp()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamp('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestampTzWithPrecision()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampTz('created_at', 1);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "created_at" datetime not null', $statements[0]);
    }

    public function testAddingTimestamps()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestamps();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(2, $statements);
        $this->assertEquals([
            'alter table "users" add column "created_at" datetime',
            'alter table "users" add column "updated_at" datetime',
        ], $statements);
    }

    public function testAddingTimestampsTz()
    {
        $blueprint = new Blueprint('users');
        $blueprint->timestampsTz();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());
        $this->assertCount(2, $statements);
        $this->assertEquals([
            'alter table "users" add column "created_at" datetime',
            'alter table "users" add column "updated_at" datetime',
        ], $statements);
    }

    public function testAddingRememberToken()
    {
        $blueprint = new Blueprint('users');
        $blueprint->rememberToken();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "remember_token" varchar', $statements[0]);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint('users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" blob not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->uuid();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "uuid" varchar not null', $statements[0]);
    }

    public function testAddingForeignUuid()
    {
        $blueprint = new Blueprint('users');
        $foreignUuid = $blueprint->foreignUuid('foo');
        $blueprint->foreignUuid('company_id')->constrained();
        $blueprint->foreignUuid('laravel_idea_id')->constrained();
        $blueprint->foreignUuid('team_id')->references('id')->on('teams');
        $blueprint->foreignUuid('team_column_id')->constrained('teams');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertInstanceOf(ForeignIdColumnDefinition::class, $foreignUuid);
        $this->assertSame([
            'alter table "users" add column "foo" varchar not null',
            'alter table "users" add column "company_id" varchar not null',
            'alter table "users" add column "laravel_idea_id" varchar not null',
            'alter table "users" add column "team_id" varchar not null',
            'alter table "users" add column "team_column_id" varchar not null',
        ], $statements);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "ip_address" varchar not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint('users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "mac_address" varchar not null', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry not null', $statements[0]);
    }

    public function testAddingPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->point('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" point not null', $statements[0]);
    }

    public function testAddingLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->linestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" linestring not null', $statements[0]);
    }

    public function testAddingPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->polygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" polygon not null', $statements[0]);
    }

    public function testAddingGeometryCollection()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->geometrycollection('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometrycollection not null', $statements[0]);
    }

    public function testAddingMultiPoint()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipoint('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" multipoint not null', $statements[0]);
    }

    public function testAddingMultiLineString()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multilinestring('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" multilinestring not null', $statements[0]);
    }

    public function testAddingMultiPolygon()
    {
        $blueprint = new Blueprint('geo');
        $blueprint->multipolygon('coordinates');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" multipolygon not null', $statements[0]);
    }

    public function testAddingGeneratedColumn()
    {
        $blueprint = new Blueprint('products');
        $blueprint->create();
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs('"price" - 5');
        $blueprint->integer('discounted_stored')->storedAs('"price" - 5');
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "products" ("price" integer not null, "discounted_virtual" integer as ("price" - 5), "discounted_stored" integer as ("price" - 5) stored)', $statements[0]);

        $blueprint = new Blueprint('products');
        $blueprint->integer('price');
        $blueprint->integer('discounted_virtual')->virtualAs('"price" - 5')->nullable(false);
        $blueprint->integer('discounted_stored')->storedAs('"price" - 5')->nullable(false);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(2, $statements);
        $expected = [
            'alter table "products" add column "price" integer not null',
            'alter table "products" add column "discounted_virtual" integer as ("price" - 5) not null',
        ];
        $this->assertSame($expected, $statements);
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

    public function testCreateTableWithVirtualAsColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_column');
        $blueprint->string('my_other_column')->virtualAs('my_column');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_column" varchar not null, "my_other_column" varchar as (my_column))', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->virtualAsJson('my_json_column->some_attribute');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_json_column" varchar not null, "my_other_column" varchar as (json_extract("my_json_column", \'$."some_attribute"\')))', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->virtualAsJson('my_json_column->some_attribute->nested');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_json_column" varchar not null, "my_other_column" varchar as (json_extract("my_json_column", \'$."some_attribute"."nested"\')))', $statements[0]);
    }

    public function testCreateTableWithVirtualAsColumnWhenJsonColumnHasArrayKey()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column')->virtualAsJson('my_json_column->foo[0][1]');

        $conn = $this->getConnection();
        $conn->shouldReceive('getConfig')->andReturn(null);

        $statements = $blueprint->toSql($conn, $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame("create table \"users\" (\"my_json_column\" varchar as (json_extract(\"my_json_column\", '$.\"foo\"[0][1]')))", $statements[0]);
    }

    public function testCreateTableWithStoredAsColumn()
    {
        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_column');
        $blueprint->string('my_other_column')->storedAs('my_column');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_column" varchar not null, "my_other_column" varchar as (my_column) stored)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->storedAsJson('my_json_column->some_attribute');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_json_column" varchar not null, "my_other_column" varchar as (json_extract("my_json_column", \'$."some_attribute"\')) stored)', $statements[0]);

        $blueprint = new Blueprint('users');
        $blueprint->create();
        $blueprint->string('my_json_column');
        $blueprint->string('my_other_column')->storedAsJson('my_json_column->some_attribute->nested');

        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("my_json_column" varchar not null, "my_other_column" varchar as (json_extract("my_json_column", \'$."some_attribute"."nested"\')) stored)', $statements[0]);
    }

    protected function getConnection()
    {
        return m::mock(Connection::class);
    }

    public function getGrammar()
    {
        return new SQLiteGrammar;
    }
}
