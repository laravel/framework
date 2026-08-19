<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Mockery;

class MockedConnectionModel extends Model
{
    public function getConnection()
    {
        $mock = Mockery::mock(Connection::class);
        $grammar = Mockery::mock(Grammar::class);
        $mock->shouldReceive('getQueryGrammar')->andReturn($grammar);
        $grammar->shouldReceive('getBitwiseOperators')->andReturn([]);
        $processor = Mockery::mock(Processor::class);
        $mock->shouldReceive('getPostProcessor')->andReturn($processor);
        $mock->shouldReceive('getName')->andReturn('name');
        $mock->shouldReceive('query')->andReturnUsing(function () use ($mock, $grammar, $processor) {
            return new BaseBuilder($mock, $grammar, $processor);
        });

        return $mock;
    }
}
