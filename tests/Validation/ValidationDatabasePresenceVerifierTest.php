<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\DatabasePresenceVerifier;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class ValidationDatabasePresenceVerifierTest extends TestCase
{
    public function testBasicCount()
    {
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $conn = Mockery::mock(ConnectionInterface::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(Builder::class);
        $conn->expects('table')->with('table')->andReturn($builder);
        $builder->expects('useWritePdo')->andReturn($builder);
        $builder->expects('where')->with('column', '=', 'value')->andReturn($builder);
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin'];
        $builder->expects('whereNull')->with('foo');
        $builder->expects('whereNotNull')->with('bar');
        $builder->expects('where')->with('baz', 'taylor');
        $builder->expects('where')->with('faz', true);
        $builder->expects('where')->with('not', '!=', 'admin');
        $builder->expects('count')->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testBasicCountWithClosures()
    {
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $conn = Mockery::mock(ConnectionInterface::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(Builder::class);
        $conn->expects('table')->with('table')->andReturn($builder);
        $builder->expects('useWritePdo')->andReturn($builder);
        $builder->expects('where')->with('column', '=', 'value')->andReturn($builder);
        $closure = function ($query) {
            $query->where('closure', 1);
        };
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin', 0 => $closure];
        $builder->expects('whereNull')->with('foo');
        $builder->expects('whereNotNull')->with('bar');
        $builder->expects('where')->with('baz', 'taylor');
        $builder->expects('where')->with('faz', true);
        $builder->expects('where')->with('not', '!=', 'admin');
        $builder->expects('where')->with(Mockery::type(Closure::class))->andReturnUsing(function () use ($builder, $closure) {
            $closure($builder);
        });
        $builder->expects('where')->with('closure', 1);
        $builder->expects('count')->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testCountNormalizesIdentityBindings()
    {
        foreach ([10 => '10', 0 => '0'] as $value => $expected) {
            $this->assertCountBindings($value, $expected);
        }

        $this->assertCountBindings(true, '1');
        $this->assertCountBindings(false, '0');
        $this->assertCountBindings('010', '010');
        $this->assertCountBindings(null, null);
    }

    protected function assertCountBindings($value, $expected)
    {
        $connection = new Connection(new PDO('sqlite::memory:'));
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $db->shouldReceive('connection')->andReturn($connection);
        $verifier = new DatabasePresenceVerifier($db);
        $extra = [fn ($query) => $query->where('owner_id', 7)];

        $queries = $connection->pretend(fn () => $verifier->getCount('table', 'column', $value, null, null, $extra));

        $this->assertSame($expected === null ? [7] : [$expected, 7], $queries[0]['bindings']);

        $queries = $connection->pretend(fn () => $verifier->getMultiCount('table', 'column', [$value, 'example'], $extra));

        $this->assertSame([$expected, 'example', 7], $queries[0]['bindings']);
    }

    public function testGetCountWithValidExcludeId()
    {
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $conn = Mockery::mock(ConnectionInterface::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(Builder::class);
        $conn->expects('table')->with('table')->andReturn($builder);
        $builder->expects('useWritePdo')->andReturn($builder);
        $builder->expects('where')->with('column', '=', 'value')->andReturn($builder);
        $builder->expects('where')->with('id', '<>', 123)->andReturn($builder);
        $builder->expects('count')->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', 123, 'id', []));
    }
}
