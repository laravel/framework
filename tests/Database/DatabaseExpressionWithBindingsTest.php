<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Query\ExpressionWithBindings as QueryExpressionWithBindings;
use Illuminate\Database\Capsule\Manager as DatabaseManager;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\ExpressionWithBindings;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseExpressionWithBindingsTest extends TestCase
{
    protected Grammar $grammar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grammar = new Grammar;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testPropertyRetrieval(): void
    {
        $sql = 'unaccent(?)';
        $bindings = ['term'];

        $expression = new ExpressionWithBindings($sql, $bindings);

        $this->assertEquals($expression->getValue($this->grammar), $sql);
        $this->assertEquals($expression->getBindings($this->grammar), $bindings);
    }

    public function testModelAttributeManipulation(): void
    {
        $geometry = 'some geojson string';
        $newId = 13;
        $ftsParams = ['foo', 'bar'];

        $connection = $this->mockModelConnection();
        $connection->shouldReceive('getPdo->lastInsertId')->andReturn($newId);

        $connection
            ->shouldReceive('insert')
            ->with(
                'insert into "my_models" ("location") values (ST_GeomFromGeoJSON(?))',
                [$geometry],
            )
            ->once();

        $connection
            ->shouldReceive('update')
            ->with(
                'update "my_models" set "fts" = to_tsvector(?, ?) where "id" = ?',
                [
                    ...$ftsParams,
                    $newId,
                ],
            )
            ->once();

        $model = new MyModel;
        $model->location = new ExpressionWithBindings('ST_GeomFromGeoJSON(?)', [$geometry]);
        $model->save();

        $model->fts = new ExpressionWithBindings('to_tsvector(?, ?)', $ftsParams);
        $model->save();
    }

    public function testExpressionCreationViaDbFacade(): void
    {
        $sql = 'ST_Transform(ST_SetSRID(ST_MakePoint(?, ?), ?), ?)';
        $coords = [56.97, 24.09];
        $srid = 4326;
        $newSrid = 3857;

        $this->bootDbFacade();

        $expression = DB::expression($sql, $coords, $srid, $newSrid);

        $this->assertInstanceOf(QueryExpressionWithBindings::class, $expression);
        $this->assertEquals($sql, $expression->getValue($this->grammar));
        $this->assertEquals([
            ...$coords,
            $srid,
            $newSrid,
        ], $expression->getBindings($this->grammar));
    }

    protected function mockModelConnection()
    {
        $processor = new Processor;

        $connection = m::mock(Connection::class)->makePartial();
        $connection->shouldReceive('getQueryGrammar')->andReturn($this->grammar);
        $connection->shouldReceive('getPostProcessor')->andReturn($processor);
        $connection->shouldReceive('query')->andReturnUsing(fn () => new Builder($connection, $this->grammar, $processor));

        Model::setConnectionResolver($resolver = m::mock(ConnectionResolverInterface::class));
        $resolver->shouldReceive('connection')->andReturn($connection);

        return $connection;
    }

    protected function bootDbFacade()
    {
        $db = new DatabaseManager;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());

        Facade::setFacadeApplication($container);
    }
}

class MyModel extends Model
{
    public $timestamps = false;
}
