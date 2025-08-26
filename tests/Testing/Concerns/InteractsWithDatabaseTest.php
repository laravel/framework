<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Expression;
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
        $grammar = 'SQLite';

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
        $grammar = 'Postgres';

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
        $grammar = 'SqlServer';

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
        $grammar = 'MySql';

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
        $grammar = 'MariaDb';

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
        $connection = m::mock(Connection::class);
        $grammarClass = 'Illuminate\Database\Query\Grammars\\'.$grammar.'Grammar';
        $grammar = new $grammarClass($connection);

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
