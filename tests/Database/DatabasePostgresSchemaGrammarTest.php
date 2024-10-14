<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\PostgresBuilder;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class DatabasePostgresSchemaGrammarTest extends TestCase
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
        $blueprint->string('name')->collation('nb_NO.utf8');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('id');
        $blueprint->string('email');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column "id" serial not null primary key',
            'alter table "users" add column "email" varchar(255) not null',
        ], $statements);
    }

    public function testAddingVector()
    {
        $blueprint = new Blueprint($this->getConnection(), 'embeddings');
        $blueprint->vector('embedding', 384);
        $statements = $blueprint->toSql($this->getConnection(), $this->getGrammar());

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "embeddings" add column "embedding" vector(384) not null', $statements[0]);
    }

    public function testCreateTableWithAutoIncrementStartingValue()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->increments('id')->startingValue(1000);
        $blueprint->string('email');
        $blueprint->string('name')->collation('nb_NO.utf8');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null, "name" varchar(255) collate "nb_NO.utf8" not null)', $statements[0]);
        $this->assertSame('alter sequence users_id_seq restart with 1000', $statements[1]);
    }

    public function testAddColumnsWithMultipleAutoIncrementStartingValue()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id()->from(100);
        $blueprint->increments('code')->from(200);
        $blueprint->string('name')->from(300);
        $statements = $blueprint->toSql();

        $this->assertEquals([
            'alter table "users" add column "id" bigserial not null primary key',
            'alter table "users" add column "code" serial not null primary key',
            'alter table "users" add column "name" varchar(255) not null',
            'alter sequence users_id_seq restart with 100',
            'alter sequence users_code_seq restart with 200',
        ], $statements);
    }

    public function testCreateTableAndCommentColumn()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->create();
        $blueprint->increments('id');
        $blueprint->string('email')->comment('my first comment');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('create table "users" ("id" serial not null primary key, "email" varchar(255) not null)', $statements[0]);
        $this->assertSame('comment on column "users"."email" is \'my first comment\'', $statements[1]);
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
        $this->assertSame('create temporary table "users" ("id" serial not null primary key, "email" varchar(255) not null)', $statements[0]);
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
        $this->assertSame('alter table "users" drop column "foo"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn(['foo', 'bar']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "foo", drop column "bar"', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropColumn('foo', 'bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "foo", drop column "bar"', $statements[0]);
    }

    public function testDropPrimary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropPrimary();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop constraint "users_pkey"', $statements[0]);
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
        $this->assertSame('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function testDropTimestampsTz()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->dropTimestampsTz();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" drop column "created_at", drop column "updated_at"', $statements[0]);
    }

    public function testDropMorphs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'photos');
        $blueprint->dropMorphs('imageable');
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('drop index "photos_imageable_type_imageable_id_index"', $statements[0]);
        $this->assertSame('alter table "photos" drop column "imageable_type", drop column "imageable_id"', $statements[1]);
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
        $this->assertSame('alter table "users" add primary key ("foo")', $statements[0]);
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

    public function testAddingIndexWithAlgorithm()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->index(['foo', 'bar'], 'baz', 'hash');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "baz" on "users" using hash ("foo", "bar")', $statements[0]);
    }

    public function testAddingFulltextIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->fulltext('body');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "users_body_fulltext" on "users" using gin ((to_tsvector(\'english\', "body")))', $statements[0]);
    }

    public function testAddingFulltextIndexMultipleColumns()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->fulltext(['body', 'title']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "users_body_title_fulltext" on "users" using gin ((to_tsvector(\'english\', "body") || to_tsvector(\'english\', "title")))', $statements[0]);
    }

    public function testAddingFulltextIndexWithLanguage()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->fulltext('body')->language('spanish');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "users_body_fulltext" on "users" using gin ((to_tsvector(\'spanish\', "body")))', $statements[0]);
    }

    public function testAddingFulltextIndexWithFluency()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('body')->fulltext();
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('create index "users_body_fulltext" on "users" using gin ((to_tsvector(\'english\', "body")))', $statements[1]);
    }

    public function testAddingSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->spatialIndex('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")', $statements[0]);
    }

    public function testAddingFluentSpatialIndex()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point')->spatialIndex();
        $statements = $blueprint->toSql();

        $this->assertCount(2, $statements);
        $this->assertSame('create index "geo_coordinates_spatialindex" on "geo" using gist ("coordinates")', $statements[1]);
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
        $this->assertSame('alter table "users" add column "id" serial not null primary key', $statements[0]);
    }

    public function testAddingSmallIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" smallserial not null primary key', $statements[0]);
    }

    public function testAddingMediumIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" serial not null primary key', $statements[0]);
    }

    public function testAddingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" bigserial not null primary key', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->id('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigserial not null primary key', $statements[0]);
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
            'alter table "users" add column "foo" bigint not null',
            'alter table "users" add column "company_id" bigint not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add column "laravel_idea_id" bigint not null',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add column "team_id" bigint not null',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add column "team_column_id" bigint not null',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingForeignIdSpecifyingIndexNameInConstraint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreignId('company_id')->constrained(indexName: 'my_index');
        $statements = $blueprint->toSql();
        $this->assertSame([
            'alter table "users" add column "company_id" bigint not null',
            'alter table "users" add constraint "my_index" foreign key ("company_id") references "companies" ("id")',
        ], $statements);
    }

    public function testAddingBigIncrementingID()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigIncrements('id');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "id" bigserial not null primary key', $statements[0]);
    }

    public function testAddingString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(255) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(100) not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo', 100)->nullable()->default('bar');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(100) null default \'bar\'', $statements[0]);
    }

    public function testAddingStringWithoutLengthLimit()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" varchar(255) not null', $statements[0]);

        Builder::$defaultStringLength = null;

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->string('foo');
        $statements = $blueprint->toSql();

        try {
            $this->assertCount(1, $statements);
            $this->assertSame('alter table "users" add column "foo" varchar not null', $statements[0]);
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
        $this->assertSame('alter table "users" add column "foo" char(255) not null', $statements[0]);

        Builder::$defaultStringLength = null;

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->char('foo');
        $statements = $blueprint->toSql();

        try {
            $this->assertCount(1, $statements);
            $this->assertSame('alter table "users" add column "foo" char not null', $statements[0]);
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
        $this->assertSame('alter table "users" add column "foo" text not null', $statements[0]);
    }

    public function testAddingBigInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->bigInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bigserial not null primary key', $statements[0]);
    }

    public function testAddingInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" serial not null primary key', $statements[0]);
    }

    public function testAddingMediumInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->mediumInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" serial not null primary key', $statements[0]);
    }

    public function testAddingTinyInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->tinyInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallserial not null primary key', $statements[0]);
    }

    public function testAddingSmallInteger()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallint not null', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->smallInteger('foo', true);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" smallserial not null primary key', $statements[0]);
    }

    public function testAddingFloat()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->float('foo', 5);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" float(5) not null', $statements[0]);
    }

    public function testAddingDouble()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->double('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" double precision not null', $statements[0]);
    }

    public function testAddingDecimal()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->decimal('foo', 5, 2);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" decimal(5, 2) not null', $statements[0]);
    }

    public function testAddingBoolean()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->boolean('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" boolean not null', $statements[0]);
    }

    public function testAddingEnum()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->enum('role', ['member', 'admin']);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "role" varchar(255) check ("role" in (\'member\', \'admin\')) not null', $statements[0]);
    }

    public function testAddingDate()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->date('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" date not null', $statements[0]);
    }

    public function testAddingYear()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->year('birth_year');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "birth_year" integer not null', $statements[0]);
    }

    public function testAddingJson()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->json('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" json not null', $statements[0]);
    }

    public function testAddingJsonb()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->jsonb('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" jsonb not null', $statements[0]);
    }

    #[DataProvider('datetimeAndPrecisionProvider')]
    public function testAddingDatetimeMethods(string $method, string $type, ?int $userPrecision, false|int|null $grammarPrecision, ?int $expected)
    {
        PostgresBuilder::defaultTimePrecision($grammarPrecision);
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->{$method}('created_at', $userPrecision);
        $statements = $blueprint->toSql();
        $type = is_null($expected) ? $type : "{$type}({$expected})";
        $with = str_contains($method, 'Tz') ? 'with' : 'without';
        $this->assertCount(1, $statements);
        $this->assertSame("alter table \"users\" add column \"created_at\" {$type} {$with} time zone not null", $statements[0]);
    }

    #[TestWith(['timestamps'])]
    #[TestWith(['timestampsTz'])]
    public function testAddingTimestamps(string $method)
    {
        PostgresBuilder::defaultTimePrecision(0);
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->{$method}();
        $statements = $blueprint->toSql();
        $with = str_contains($method, 'Tz') ? 'with' : 'without';
        $this->assertCount(2, $statements);
        $this->assertSame([
            "alter table \"users\" add column \"created_at\" timestamp(0) {$with} time zone null",
            "alter table \"users\" add column \"updated_at\" timestamp(0) {$with} time zone null",
        ], $statements);
    }

    public function testAddingBinary()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->binary('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" bytea not null', $statements[0]);
    }

    public function testAddingUuid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" uuid not null', $statements[0]);
    }

    public function testAddingUuidDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->uuid();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "uuid" uuid not null', $statements[0]);
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
            'alter table "users" add column "foo" uuid not null',
            'alter table "users" add column "company_id" uuid not null',
            'alter table "users" add constraint "users_company_id_foreign" foreign key ("company_id") references "companies" ("id")',
            'alter table "users" add column "laravel_idea_id" uuid not null',
            'alter table "users" add constraint "users_laravel_idea_id_foreign" foreign key ("laravel_idea_id") references "laravel_ideas" ("id")',
            'alter table "users" add column "team_id" uuid not null',
            'alter table "users" add constraint "users_team_id_foreign" foreign key ("team_id") references "teams" ("id")',
            'alter table "users" add column "team_column_id" uuid not null',
            'alter table "users" add constraint "users_team_column_id_foreign" foreign key ("team_column_id") references "teams" ("id")',
        ], $statements);
    }

    public function testAddingGeneratedAs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('foo')->generatedAs();
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity primary key', $statements[0]);
        // With always modifier
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('foo')->generatedAs()->always();
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null generated always as identity primary key', $statements[0]);
        // With sequence options
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->increments('foo')->generatedAs('increment by 10 start with 100');
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity (increment by 10 start with 100) primary key', $statements[0]);
        // Not a primary key
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo')->generatedAs();
        $statements = $blueprint->toSql();
        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" integer not null generated by default as identity', $statements[0]);
    }

    public function testAddingVirtualAs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->virtualAs('foo is not null');
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column "foo" integer null',
            'alter table "users" add column "bar" boolean not null generated always as (foo is not null)',
        ], $statements);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->virtualAs(new Expression('foo is not null'));
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column "foo" integer null',
            'alter table "users" add column "bar" boolean not null generated always as (foo is not null)',
        ], $statements);
    }

    public function testAddingStoredAs()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->storedAs('foo is not null');
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column "foo" integer null',
            'alter table "users" add column "bar" boolean not null generated always as (foo is not null) stored',
        ], $statements);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->integer('foo')->nullable();
        $blueprint->boolean('bar')->storedAs(new Expression('foo is not null'));
        $statements = $blueprint->toSql();
        $this->assertCount(2, $statements);
        $this->assertSame([
            'alter table "users" add column "foo" integer null',
            'alter table "users" add column "bar" boolean not null generated always as (foo is not null) stored',
        ], $statements);
    }

    public function testAddingIpAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" inet not null', $statements[0]);
    }

    public function testAddingIpAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->ipAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "ip_address" inet not null', $statements[0]);
    }

    public function testAddingMacAddress()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress('foo');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "foo" macaddr not null', $statements[0]);
    }

    public function testAddingMacAddressDefaultsColumnName()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->macAddress();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add column "mac_address" macaddr not null', $statements[0]);
    }

    public function testCompileForeign()
    {
        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable(false)->initiallyImmediate();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade not deferrable', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->initiallyImmediate(false);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable initially deferred', $statements[0]);

        $blueprint = new Blueprint($this->getConnection(), 'users');
        $blueprint->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade')->deferrable()->notValid();
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "users" add constraint "users_parent_id_foreign" foreign key ("parent_id") references "parents" ("id") on delete cascade deferrable not valid', $statements[0]);
    }

    public function testAddingGeometry()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry not null', $statements[0]);
    }

    public function testAddingGeography()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geography('coordinates', 'pointzm', 4269);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geography(pointzm,4269) not null', $statements[0]);
    }

    public function testAddingPoint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(point) not null', $statements[0]);
    }

    public function testAddingPointWithSrid()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'point', 4269);
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(point,4269) not null', $statements[0]);
    }

    public function testAddingLineString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'linestring');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(linestring) not null', $statements[0]);
    }

    public function testAddingPolygon()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'polygon');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(polygon) not null', $statements[0]);
    }

    public function testAddingGeometryCollection()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'geometrycollection');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(geometrycollection) not null', $statements[0]);
    }

    public function testAddingMultiPoint()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multipoint');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(multipoint) not null', $statements[0]);
    }

    public function testAddingMultiLineString()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multilinestring');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(multilinestring) not null', $statements[0]);
    }

    public function testAddingMultiPolygon()
    {
        $blueprint = new Blueprint($this->getConnection(), 'geo');
        $blueprint->geometry('coordinates', 'multipolygon');
        $statements = $blueprint->toSql();

        $this->assertCount(1, $statements);
        $this->assertSame('alter table "geo" add column "coordinates" geometry(multipolygon) not null', $statements[0]);
    }

    public function testCreateDatabase()
    {
        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_foo');
        $statement = $this->getGrammar()->compileCreateDatabase('my_database_a', $connection);

        $this->assertSame(
            'create database "my_database_a" encoding "utf8_foo"',
            $statement
        );

        $connection = $this->getConnection();
        $connection->shouldReceive('getConfig')->once()->once()->with('charset')->andReturn('utf8_bar');
        $statement = $this->getGrammar()->compileCreateDatabase('my_database_b', $connection);

        $this->assertSame(
            'create database "my_database_b" encoding "utf8_bar"',
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

    public function testDropAllTablesEscapesTableNames()
    {
        $statement = $this->getGrammar()->compileDropAllTables(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop table "alpha","beta","gamma" cascade', $statement);
    }

    public function testDropAllViewsEscapesTableNames()
    {
        $statement = $this->getGrammar()->compileDropAllViews(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop view "alpha","beta","gamma" cascade', $statement);
    }

    public function testDropAllTypesEscapesTableNames()
    {
        $statement = $this->getGrammar()->compileDropAllTypes(['alpha', 'beta', 'gamma']);

        $this->assertSame('drop type "alpha","beta","gamma" cascade', $statement);
    }

    public function testCompileColumns()
    {
        $connection = $this->getConnection();
        $grammar = $this->getGrammar();

        $connection->shouldReceive('getServerVersion')->once()->andReturn('12.0.0');
        $grammar->setConnection($connection);

        $statement = $grammar->compileColumns('public', 'table');

        $this->assertStringContainsString("where c.relname = 'table' and n.nspname = 'public'", $statement);
    }

    protected function getConnection(
        ?PostgresGrammar $grammar = null,
        ?PostgresBuilder $builder = null
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
        return new PostgresGrammar;
    }

    public function getBuilder()
    {
        return mock(PostgresBuilder::class);
    }

    /** @return list<array{method: string, type: string, user: int|null, grammar: false|int|null, expected: int|null}> */
    public static function datetimeAndPrecisionProvider(): array
    {
        $methods = [
            ['method' => 'datetime', 'type' => 'timestamp'],
            ['method' => 'datetimeTz', 'type' => 'timestamp'],
            ['method' => 'timestamp', 'type' => 'timestamp'],
            ['method' => 'timestampTz', 'type' => 'timestamp'],
            ['method' => 'time', 'type' => 'time'],
            ['method' => 'timeTz', 'type' => 'time'],
        ];
        $precisions = [
            'user can override grammar default' => ['userPrecision' => 1, 'grammarPrecision' => null, 'expected' => 1],
            'fallback to grammar default' => ['userPrecision' => null, 'grammarPrecision' => 5, 'expected' => 5],
            'fallback to database default' => ['userPrecision' => null, 'grammarPrecision' => null, 'expected' => null],
        ];

        $result = [];

        foreach ($methods as $datetime) {
            foreach ($precisions as $precision) {
                $result[] = array_merge($datetime, $precision);
            }
        }

        return $result;
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
