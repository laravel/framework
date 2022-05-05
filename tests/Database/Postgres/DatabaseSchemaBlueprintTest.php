<?php

namespace Illuminate\Tests\Database\Postgres;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBlueprintTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        Builder::$defaultMorphKeyType = 'int';
    }

    public function testDefaultCurrentDateTime()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->dateTime('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new PostgresGrammar));
    }

    public function testDefaultCurrentTimestamp()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new PostgresGrammar));
    }

    public function testTinyTextColumn()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->tinyText('note');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table "posts" add column "note" varchar(255) not null',
        ], $blueprint->toSql($connection, new PostgresGrammar));
    }

    public function testTinyTextNullableColumn()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->tinyText('note')->nullable();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table "posts" add column "note" varchar(255) null',
        ], $blueprint->toSql($connection, new PostgresGrammar));
    }

    public function testTableComment()
    {
        $blueprint = new Blueprint('posts', function (Blueprint $table) {
            $table->comment('Look at my comment, it is amazing');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'comment on table "posts" is \'Look at my comment, it is amazing\'',
        ], $blueprint->toSql($connection, new PostgresGrammar));
    }
}
