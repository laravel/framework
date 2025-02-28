<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Validation\DatabasePresenceVerifier;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationDatabasePresenceVerifierTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBasicCount()
    {
        $verifier = new DatabasePresenceVerifier($db = m::mock(ConnectionResolverInterface::class));
        $verifier->setConnection('connection');
        $db->shouldReceive('connection')->once()->with('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('table')->once()->with('table')->andReturn($builder = m::mock(stdClass::class));
        $builder->shouldReceive('useWritePdo')->once()->andReturn($builder);
        $builder->shouldReceive('where')->with('column', '=', 'value')->andReturn($builder);
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin'];
        $builder->shouldReceive('whereNull')->with('foo');
        $builder->shouldReceive('whereNotNull')->with('bar');
        $builder->shouldReceive('where')->with('baz', 'taylor');
        $builder->shouldReceive('where')->with('faz', true);
        $builder->shouldReceive('where')->with('not', '!=', 'admin');
        $builder->shouldReceive('count')->once()->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testBasicCountWithClosures()
    {
        $verifier = new DatabasePresenceVerifier($db = m::mock(ConnectionResolverInterface::class));
        $verifier->setConnection('connection');
        $db->shouldReceive('connection')->once()->with('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('table')->once()->with('table')->andReturn($builder = m::mock(stdClass::class));
        $builder->shouldReceive('useWritePdo')->once()->andReturn($builder);
        $builder->shouldReceive('where')->with('column', '=', 'value')->andReturn($builder);
        $closure = function ($query) {
            $query->where('closure', 1);
        };
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin', 0 => $closure];
        $builder->shouldReceive('whereNull')->with('foo');
        $builder->shouldReceive('whereNotNull')->with('bar');
        $builder->shouldReceive('where')->with('baz', 'taylor');
        $builder->shouldReceive('where')->with('faz', true);
        $builder->shouldReceive('where')->with('not', '!=', 'admin');
        $builder->shouldReceive('where')->with(m::type(Closure::class))->andReturnUsing(function () use ($builder, $closure) {
            $closure($builder);
        });
        $builder->shouldReceive('where')->with('closure', 1);
        $builder->shouldReceive('count')->once()->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testGetCountWithValidExcludeId()
    {
        $verifier = new DatabasePresenceVerifier($db = m::mock(ConnectionResolverInterface::class));
        $verifier->setConnection('connection');
        $db->shouldReceive('connection')->once()->with('connection')->andReturn($conn = m::mock(stdClass::class));
        $conn->shouldReceive('table')->once()->with('table')->andReturn($builder = m::mock(stdClass::class));
        $builder->shouldReceive('useWritePdo')->once()->andReturn($builder);
        $builder->shouldReceive('where')->with('column', '=', 'value')->andReturn($builder);
        $builder->shouldReceive('where')->with('id', '<>', 123)->andReturn($builder);
        $builder->shouldReceive('count')->once()->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', 123, 'id', []));
    }
}
