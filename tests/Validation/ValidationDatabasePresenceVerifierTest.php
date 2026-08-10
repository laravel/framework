<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Validation\DatabasePresenceVerifier;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationDatabasePresenceVerifierTest extends TestCase
{
    public function testBasicCount()
    {
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $conn = Mockery::mock(stdClass::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(stdClass::class);
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
        $conn = Mockery::mock(stdClass::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(stdClass::class);
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

    public function testGetCountWithValidExcludeId()
    {
        $db = Mockery::mock(ConnectionResolverInterface::class);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $conn = Mockery::mock(stdClass::class);
        $db->expects('connection')->with('connection')->andReturn($conn);
        $builder = Mockery::mock(stdClass::class);
        $conn->expects('table')->with('table')->andReturn($builder);
        $builder->expects('useWritePdo')->andReturn($builder);
        $builder->expects('where')->with('column', '=', 'value')->andReturn($builder);
        $builder->expects('where')->with('id', '<>', 123)->andReturn($builder);
        $builder->expects('count')->andReturn(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', 123, 'id', []));
    }
}
