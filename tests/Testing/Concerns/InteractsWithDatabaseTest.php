<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InteractsWithDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testCastToJsonSqlite()
    {
        $grammar = new SQLiteGrammar();

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonPostgres()
    {
        $grammar = new PostgresGrammar();

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '["foo","bar"]'
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        '{"foo":"bar"}'
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonSqlServer()
    {
        $grammar = new SqlServerGrammar();

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]')
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('{"foo":"bar"}')
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonMySql()
    {
        $grammar = new MySqlGrammar();

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('["foo","bar"]' as json)
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        cast('{"foo":"bar"}' as json)
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    public function testCastToJsonMariaDb()
    {
        $grammar = new MariaDbGrammar();

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]', '$')
        TEXT,
            $this->castAsJson(['foo', 'bar'], $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('["foo","bar"]', '$')
        TEXT,
            $this->castAsJson(collect(['foo', 'bar']), $grammar)
        );

        $this->assertEquals(<<<'TEXT'
        json_query('{"foo":"bar"}', '$')
        TEXT,
            $this->castAsJson((object) ['foo' => 'bar'], $grammar)
        );
    }

    protected function castAsJson($value, $grammar)
    {
        $connection = m::mock(ConnectionInterface::class);

        $connection->shouldReceive('getQueryGrammar')->andReturn($grammar);

        $connection->shouldReceive('raw')->andReturnUsing(function ($value) {
            return new Expression($value);
        });

        $connection->shouldReceive('getPdo->quote')->andReturnUsing(function ($value) {
            return "'".$value."'";
        });

        DB::shouldReceive('connection')->with(null)->andReturn($connection);

        $instance = new class
        {
            use InteractsWithDatabase;
        };

        return $instance->castAsJson($value)->getValue($grammar);
    }
}
