<?php

namespace Illuminate\Tests\Session;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Session\DatabaseSessionHandler;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseSessionHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testItAlwaysUsesWritePdo()
    {
        $connection = m::mock(ConnectionInterface::class);
        $builder = m::mock(Builder::class);

        $builder->shouldReceive('useWritePdo')
            ->once()
            ->andReturnSelf();

        $connection->shouldReceive('table')
            ->andReturn($builder);

        $databaseSessionHandler = new DatabaseSessionHandler($connection, 'sessions', 120);
        $reflection = new ReflectionClass($databaseSessionHandler);
        $method = $reflection->getMethod('getQuery');
        $method->setAccessible(true);
        $method->invoke($databaseSessionHandler);
    }
}
