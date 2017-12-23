<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Database\Schema\Grammars\SqlServerGrammar;

class DatabaseSchemaBlueprintTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testToSqlRunsCommandsFromBlueprint()
    {
        $conn = m::mock('Illuminate\Database\Connection');
        $conn->shouldReceive('statement')->once()->with('foo');
        $conn->shouldReceive('statement')->once()->with('bar');
        $grammar = m::mock('Illuminate\Database\Schema\Grammars\MySqlGrammar');
        $blueprint = $this->getMockBuilder('Illuminate\Database\Schema\Blueprint')->setMethods(['toSql'])->setConstructorArgs(['users'])->getMock();
        $blueprint->expects($this->once())->method('toSql')->with($this->equalTo($conn), $this->equalTo($grammar))->will($this->returnValue(['foo', 'bar']));

        $blueprint->build($conn, $grammar);
    }

    public function testIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->unique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->index('foo');
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->spatialIndex('coordinates');
        $commands = $blueprint->getCommands();
        $this->assertEquals('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDropIndexDefaultNames()
    {
        $blueprint = new Blueprint('users');
        $blueprint->dropUnique(['foo', 'bar']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_bar_unique', $commands[0]->index);

        $blueprint = new Blueprint('users');
        $blueprint->dropIndex(['foo']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('users_foo_index', $commands[0]->index);

        $blueprint = new Blueprint('geo');
        $blueprint->dropSpatialIndex(['coordinates']);
        $commands = $blueprint->getCommands();
        $this->assertEquals('geo_coordinates_spatialindex', $commands[0]->index);
    }

    public function testDefaultCurrentTimestamp()
    {
        $base = new Blueprint('users', function ($table) {
            $table->timestamp('created')->useCurrent();
        });

        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` add `created` timestamp default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new MySqlGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" timestamp(0) without time zone default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new PostgresGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add column "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new SQLiteGrammar));

        $blueprint = clone $base;
        $this->assertEquals(['alter table "users" add "created" datetime default CURRENT_TIMESTAMP not null'], $blueprint->toSql($connection, new SqlServerGrammar));
    }

    public function testUnsignedDecimalTable()
    {
        $base = new Blueprint('users', function ($table) {
            $table->unsignedDecimal('money', 10, 2)->useCurrent();
        });

        $connection = m::mock('Illuminate\Database\Connection');

        $blueprint = clone $base;
        $this->assertEquals(['alter table `users` add `money` decimal(10, 2) unsigned not null'], $blueprint->toSql($connection, new MySqlGrammar));
    }
}
