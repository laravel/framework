<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Limiters\ConcurrencyLimiter;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ConcurrencyLimiterTest extends TestCase
{
    public function testAcquireUsesHashTagsOnPhpRedisClusterConnection()
    {
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        // acquire() calls eval → command('eval', ...) with the lock script
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget')
                && $args[2] === 3
                && $args[1][0] === '{test-limiter}1'
                && $args[1][1] === '{test-limiter}2'
                && $args[1][2] === '{test-limiter}3'
                && $args[1][3] === '{test-limiter}'; // ARGV[1] = hash-tagged prefix
        }))->andReturn('{test-limiter}1');

        // release() also calls eval → command('eval', ...) with the release script
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{test-limiter}1'; // released key matches acquired key
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'test-limiter', 3, 60);
        $result = $limiter->block(0, function () {
            return 'executed';
        });

        $this->assertSame('executed', $result);
    }

    public function testAcquireUsesPlainKeysOnNonClusterConnection()
    {
        $connection = m::mock(PhpRedisConnection::class);
        $connection->expects('isCluster')->andReturn(false);

        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget')
                && $args[2] === 2
                && $args[1][0] === 'mylock1'
                && $args[1][1] === 'mylock2'
                && $args[1][2] === 'mylock'; // ARGV[1] = plain name
        }))->andReturn('mylock1');

        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === 'mylock1';
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'mylock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'done';
        });

        $this->assertSame('done', $result);
    }

    public function testAcquireUsesHashTagsOnPredisClusterConnection()
    {
        $connection = m::mock(PredisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        $connection->expects('eval')->with(
            m::on(fn ($s) => str_contains($s, 'mget')),
            2,
            '{limiter}1', '{limiter}2',
            '{limiter}', m::any(), m::any()
        )->andReturn('{limiter}1');

        $connection->expects('eval')->with(
            m::on(fn ($s) => str_contains($s, 'del')),
            1,
            '{limiter}1', m::any()
        )->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'limiter', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testReleaseKeyMatchesAcquireKeyOnCluster()
    {
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        // Acquire returns the slot key
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget');
        }))->andReturn('{mykey}2');

        // Release should be called with the exact same key
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{mykey}2';
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'mykey', 3, 60);
        $limiter->block(0, function () {
            // callback runs between acquire and release
        });
    }

    public function testAcquireDoesNotDoubleWrapPreExistingHashTags()
    {
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        // Name already has hash tags — should NOT be double-wrapped
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{mylock}1'
                && $args[1][1] === '{mylock}2'
                && $args[1][2] === '{mylock}'; // ARGV[1] = unchanged name with existing tags
        }))->andReturn('{mylock}1');

        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{mylock}1';
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, '{mylock}', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireWrapsUnmatchedBraceOnCluster()
    {
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        // Name has '{' but no '}' — not a valid hash tag, should be wrapped
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{my{lock}1'
                && $args[1][1] === '{my{lock}2'
                && $args[1][2] === '{my{lock}'; // ARGV[1] = wrapped prefix
        }))->andReturn('{my{lock}1');

        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{my{lock}1';
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'my{lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireWrapsEmptyBracesOnCluster()
    {
        $connection = m::mock(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->andReturn(true);

        // Name has '{}' but that's an empty hash tag — should be wrapped
        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{my{}lock}1'
                && $args[1][1] === '{my{}lock}2'
                && $args[1][2] === '{my{}lock}'; // ARGV[1] = wrapped prefix
        }))->andReturn('{my{}lock}1');

        $connection->expects('command')->with('eval', m::on(function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{my{}lock}1';
        }))->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'my{}lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireUsesPlainKeysOnPredisNonClusterConnection()
    {
        $connection = m::mock(PredisConnection::class);
        $connection->expects('isCluster')->andReturn(false);

        $connection->expects('eval')->with(
            m::on(fn ($s) => str_contains($s, 'mget')),
            2,
            'lock1', 'lock2',
            'lock', m::any(), m::any()
        )->andReturn('lock1');

        $connection->expects('eval')->with(
            m::on(fn ($s) => str_contains($s, 'del')),
            1,
            'lock1', m::any()
        )->andReturn(1);

        $limiter = new ConcurrencyLimiter($connection, 'lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'success';
        });

        $this->assertSame('success', $result);
    }
}
