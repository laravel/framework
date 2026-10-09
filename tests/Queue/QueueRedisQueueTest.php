<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\Queue;
use Illuminate\Queue\RedisQueue;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

class QueueRedisQueueTest extends TestCase
{
    use VerifiesDoubles;

    public function testGetQueueRemainsUnchangedForNonCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new RedisQueue($redis, 'default');
        $this->assertSame('queues:default', $queue->getQueue(null));
        $this->assertSame('queues:emails', $queue->getQueue('emails'));
    }

    public function testGetQueueRemainsUnchangedForCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new RedisQueue($redis, 'default');

        // getQueue() should NOT add hash tags — it's unchanged
        $this->assertSame('queues:default', $queue->getQueue(null));
        $this->assertSame('queues:emails', $queue->getQueue('emails'));
    }

    public function testGetRedisKeyReturnsPlainKeyForNonCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(\Illuminate\Redis\Connections\Connection::class);
        $connection->expects('isCluster')->returns(false);
        $redis->expects('connection')->returns($connection);

        $this->assertSame('queues:default', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:emails', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyWrapsWithHashTagsForPhpRedisCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{emails}', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyWrapsWithHashTagsForPredisCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PredisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{emails}', $queue->testGetQueueRedisKey('emails'));
    }

    public function testGetRedisKeyDoesNotDoubleWrapExistingHashTags()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, '{default}');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        $this->assertSame('queues:{default}', $queue->testGetQueueRedisKey(null));
        $this->assertSame('queues:{custom}', $queue->testGetQueueRedisKey('{custom}'));
    }

    public function testGetRedisKeySkipsWrappingWhenQueueNameContainsBraces()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Queue name already contains hash tags — skip wrapping
        $this->assertSame('queues:process-{batch}-results', $queue->testGetQueueRedisKey('process-{batch}-results'));
    }

    public function testGetRedisKeyWrapsEmptyHashTagOnCluster()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Empty braces '{}' are not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{my{}queue}', $queue->testGetQueueRedisKey('my{}queue'));
    }

    public function testGetRedisKeyWrapsUnmatchedOpeningBrace()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Unmatched '{' is not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{my{broken}', $queue->testGetQueueRedisKey('my{broken'));
    }

    public function testGetRedisKeyWrapsUnmatchedClosingBrace()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Unmatched '}' is not a valid hash tag — should still get wrapped
        $this->assertSame('queues:{broken}queue}', $queue->testGetQueueRedisKey('broken}queue'));
    }

    public function testGetRedisKeyWrapsEmptyFirstHashTagFollowedByValidPair()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Redis spec: the first '{}' is an empty hash tag, so the whole key is hashed
        // even though '{bar}' looks valid. Must be wrapped to ensure slot affinity.
        $this->assertSame('queues:{foo{}{bar}}', $queue->testGetQueueRedisKey('foo{}{bar}'));
    }

    public function testIsClusterConnectionCachesResult()
    {
        $redis = Double::for(Factory::class);
        $queue = new TestableRedisQueue($redis, 'default');
        $connection = Double::for(PhpRedisClusterConnection::class);
        $connection->expects('isCluster')->returns(true);
        $redis->expects('connection')->returns($connection);

        // Multiple calls should only trigger one connection() call
        $this->assertTrue($queue->testIsClusterConnection());
        $this->assertTrue($queue->testIsClusterConnection());
        $this->assertTrue($queue->testIsClusterConnection());
    }

    public function testAllQueueNamesStripsClusterBraces()
    {
        $redis = Double::for(Factory::class);
        $connection = Double::for(PhpRedisConnection::class)->passthru();
        $redis->expects('connection')->returns($connection);
        $connection->expects('command')->with('keys', Argument::any())->returns(['queues:{default}', 'queues:{default}:delayed', 'queues:{emails}']);
        $queue = new TestableRedisQueue($redis, 'default');

        $this->assertSame(['default', 'emails'], $queue->testAllQueueNames()->all());
    }

    #[RequiresPhpExtension('redis')]
    public function testScanningQueueNamesDoesNotDoublePrefixTheMatchPattern()
    {
        $redis = Double::for(Factory::class);
        $connection = Double::for(PhpRedisClusterConnection::class);
        $client = new class
        {
            public function getOption($option)
            {
                return $option === \Redis::OPT_SCAN ? \Redis::SCAN_PREFIX : null;
            }
        };

        $redis->expects('connection')->returns($connection);
        $connection->expects('client')->returns($client);
        $connection->expects('scan')->with(null, ['match' => 'queues:*', 'count' => 1000])->returns([null, ['test_queues:{default}']]);

        $queue = new TestableRedisQueue($redis, 'default');

        $this->assertSame(['default'], $queue->testAllQueueNames()->all());
    }

}

class TestableRedisQueue extends RedisQueue
{
    public function testGetQueueRedisKey($queue = null)
    {
        return $this->getQueueRedisKey($queue);
    }

    public function testIsClusterConnection()
    {
        return $this->isClusterConnection();
    }

    public function testAllQueueNames()
    {
        return $this->allQueueNames();
    }
}
