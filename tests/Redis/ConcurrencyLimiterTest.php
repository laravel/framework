<?php

namespace Illuminate\Tests\Redis;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Redis\Limiters\ConcurrencyLimiter;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class ConcurrencyLimiterTest extends TestCase
{
    use VerifiesDoubles;

    public function testAcquireUsesHashTagsOnPhpRedisClusterConnection()
    {
        $connection = Double::for(PhpRedisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // acquire() calls eval → command('eval', ...) with the lock script
        // release() also calls eval → command('eval', ...) with the release script
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[2] === 3
                && $args[1][0] === '{test-limiter}1'
                && $args[1][1] === '{test-limiter}2'
                && $args[1][2] === '{test-limiter}3'
                && $args[1][3] === '{test-limiter}'; // ARGV[1] = hash-tagged prefix
        }, '{test-limiter}1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{test-limiter}1'; // released key matches acquired key
        });

        $limiter = new ConcurrencyLimiter($connection, 'test-limiter', 3, 60);
        $result = $limiter->block(0, function () {
            return 'executed';
        });

        $this->assertSame('executed', $result);
    }

    public function testAcquireUsesPlainKeysOnNonClusterConnection()
    {
        $connection = Double::for(PhpRedisConnection::class)->passthru();
        $connection->expects('isCluster')->returns(false);

        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[2] === 2
                && $args[1][0] === 'mylock1'
                && $args[1][1] === 'mylock2'
                && $args[1][2] === 'mylock'; // ARGV[1] = plain name
        }, 'mylock1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === 'mylock1';
        });

        $limiter = new ConcurrencyLimiter($connection, 'mylock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'done';
        });

        $this->assertSame('done', $result);
    }

    public function testAcquireUsesHashTagsOnPredisClusterConnection()
    {
        $connection = Double::for(PredisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // Predis forwards eval() through __call() into command('eval', [script, numkeys, ...keys, ...argv]).
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1] === 2
                && $args[2] === '{limiter}1'
                && $args[3] === '{limiter}2'
                && $args[4] === '{limiter}';
        }, '{limiter}1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1] === 1
                && $args[2] === '{limiter}1';
        });

        $limiter = new ConcurrencyLimiter($connection, 'limiter', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testReleaseKeyMatchesAcquireKeyOnCluster()
    {
        $connection = Double::for(PhpRedisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // Acquire returns the slot key
        // Release should be called with the exact same key
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget');
        }, '{mykey}2', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{mykey}2';
        });

        $limiter = new ConcurrencyLimiter($connection, 'mykey', 3, 60);
        $limiter->block(0, function () {
            // callback runs between acquire and release
        });
    }

    public function testAcquireDoesNotDoubleWrapPreExistingHashTags()
    {
        $connection = Double::for(PhpRedisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // Name already has hash tags — should NOT be double-wrapped
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{mylock}1'
                && $args[1][1] === '{mylock}2'
                && $args[1][2] === '{mylock}'; // ARGV[1] = unchanged name with existing tags
        }, '{mylock}1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{mylock}1';
        });

        $limiter = new ConcurrencyLimiter($connection, '{mylock}', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireWrapsUnmatchedBraceOnCluster()
    {
        $connection = Double::for(PhpRedisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // Name has '{' but no '}' — not a valid hash tag, should be wrapped
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{my{lock}1'
                && $args[1][1] === '{my{lock}2'
                && $args[1][2] === '{my{lock}'; // ARGV[1] = wrapped prefix
        }, '{my{lock}1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{my{lock}1';
        });

        $limiter = new ConcurrencyLimiter($connection, 'my{lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireWrapsEmptyBracesOnCluster()
    {
        $connection = Double::for(PhpRedisClusterConnection::class)->passthru();
        $connection->expects('isCluster')->returns(true);

        // Name has '{}' but that's an empty hash tag — should be wrapped
        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1][0] === '{my{}lock}1'
                && $args[1][1] === '{my{}lock}2'
                && $args[1][2] === '{my{}lock}'; // ARGV[1] = wrapped prefix
        }, '{my{}lock}1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1][0] === '{my{}lock}1';
        });

        $limiter = new ConcurrencyLimiter($connection, 'my{}lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    public function testAcquireUsesPlainKeysOnPredisNonClusterConnection()
    {
        $connection = Double::for(PredisConnection::class)->passthru();
        $connection->expects('isCluster')->returns(false);

        $this->expectsEval($connection, function ($args) {
            return str_contains($args[0], 'mget')
                && $args[1] === 2
                && $args[2] === 'lock1'
                && $args[3] === 'lock2'
                && $args[4] === 'lock';
        }, 'lock1', function ($args) {
            return str_contains($args[0], 'del')
                && $args[1] === 1
                && $args[2] === 'lock1';
        });

        $limiter = new ConcurrencyLimiter($connection, 'lock', 2, 60);
        $result = $limiter->block(0, function () {
            return 'ok';
        });

        $this->assertSame('ok', $result);
    }

    /**
     * Expect the acquire (mget) and release (del) eval commands, in either order.
     */
    protected function expectsEval($connection, callable $acquire, string $acquiredKey, callable $release): void
    {
        $connection->expects('command')->with('eval', Argument::any())->times(2)->resolves(function ($command, $args) use ($acquire, $acquiredKey, $release) {
            if (str_contains($args[0], 'mget')) {
                $this->assertTrue($acquire($args));

                return $acquiredKey;
            }

            $this->assertTrue($release($args));

            return 1;
        });
    }
}
