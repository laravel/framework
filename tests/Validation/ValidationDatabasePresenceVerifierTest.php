<?php

namespace Illuminate\Tests\Validation;

use JMac\Testing\Matching\Argument;
use JMac\Testing\Double;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\DatabasePresenceVerifier;
use Mockery;
use PHPUnit\Framework\TestCase;

class ValidationDatabasePresenceVerifierTest extends TestCase
{
    public function testBasicCount()
    {
        $conn = Double::for(ConnectionInterface::class);
        $db = new ConnectionResolver;
        $db->addConnection('connection', $conn);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $builder = Double::for(Builder::class);
        $conn->expects('table')->with('table')->returns($builder);
        $builder->expects('useWritePdo')->returns($builder);
        $builder->expects('where')->with('column', '=', 'value')->returns($builder);
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin'];
        $builder->expects('whereNull')->with('foo');
        $builder->expects('whereNotNull')->with('bar');
        $builder->expects('where')->with('baz', 'taylor');
        $builder->expects('where')->with('faz', true);
        $builder->expects('where')->with('not', '!=', 'admin');
        $builder->expects('count')->returns(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testBasicCountWithClosures()
    {
        $conn = Double::for(ConnectionInterface::class);
        $db = new ConnectionResolver;
        $db->addConnection('connection', $conn);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $builder = Double::for(Builder::class);
        $conn->expects('table')->with('table')->returns($builder);
        $builder->expects('useWritePdo')->returns($builder);
        $builder->expects('where')->with('column', '=', 'value')->returns($builder);
        $closure = function ($query) {
            $query->where('closure', 1);
        };
        $extra = ['foo' => 'NULL', 'bar' => 'NOT_NULL', 'baz' => 'taylor', 'faz' => true, 'not' => '!admin', 0 => $closure];
        $builder->expects('whereNull')->with('foo');
        $builder->expects('whereNotNull')->with('bar');
        $builder->expects('where')->with('baz', 'taylor');
        $builder->expects('where')->with('faz', true);
        $builder->expects('where')->with('not', '!=', 'admin');
        $builder->expects('where')->with(Argument::type(Closure::class))->resolves(function () use ($builder, $closure) {
            $closure($builder);
        });
        $builder->expects('where')->with('closure', 1);
        $builder->expects('count')->returns(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', null, null, $extra));
    }

    public function testGetCountWithValidExcludeId()
    {
        $conn = Double::for(ConnectionInterface::class);
        $db = new ConnectionResolver;
        $db->addConnection('connection', $conn);
        $verifier = new DatabasePresenceVerifier($db);
        $verifier->setConnection('connection');
        $builder = Double::for(Builder::class);
        $conn->expects('table')->with('table')->returns($builder);
        $builder->expects('useWritePdo')->returns($builder);
        $builder->expects('where')->with('column', '=', 'value')->returns($builder);
        $builder->expects('where')->with('id', '<>', 123)->returns($builder);
        $builder->expects('count')->returns(100);

        $this->assertEquals(100, $verifier->getCount('table', 'column', 'value', 123, 'id', []));
    }
}
