<?php

namespace Illuminate\Tests\Database\SqlServer;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;
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

        $this->assertEquals(['alter table "users" add "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testDefaultCurrentTimestamp()
    {
        $blueprint = new Blueprint('users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals(['alter table "users" add "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testTinyTextColumn()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->tinyText('note');
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table "posts" add "note" nvarchar(255) not null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testTinyTextNullableColumn()
    {
        $blueprint = new Blueprint('posts', function ($table) {
            $table->tinyText('note')->nullable();
        });

        $connection = m::mock(Connection::class);

        $this->assertEquals([
            'alter table "posts" add "note" nvarchar(255) null',
        ], $blueprint->toSql($connection, new SqlServerGrammar));
    }
}
