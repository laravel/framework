<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('redis')]
class PhpRedisClusterConnectionTest extends TestCase
{
    public function testItScansUsingDefaultNode()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([['127.0.0.1', '6379']]);
        $client->expects('scan')
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
    }

    public function testItOnlyFetchesDefaultNodeOnce()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([['127.0.0.1', '6379']]);
        $client->expects('scan')->times(2);

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
        $connection->scan(0);
    }

    public function testItScansUsingOptionNode()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('scan')
            ->with(0, 'option-node', '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0, ['node' => 'option-node']));
    }

    public function testItThrowsExceptionWithoutNodes()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([]);
        $client->shouldNotReceive('scan');

        $this->expectExceptionObject(new InvalidArgumentException('Unable to determine default node. No master nodes found in the cluster.'));

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
    }

    public function testItReturnsFalseWhenCursorIsZeroAndResultIsEmpty()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([['127.0.0.1', '6379']]);
        $client->expects('scan')
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(false);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertFalse($connection->scan(0));
    }

    public function testItFlushesAllMasterNodes()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([
            ['127.0.0.1', '6379'],
            ['127.0.0.2', '6379'],
        ]);
        $client->expects('flushdb')->with(['127.0.0.1', '6379']);
        $client->expects('flushdb')->with(['127.0.0.2', '6379']);

        $connection = new PhpRedisClusterConnection($client);
        $connection->flushdb();
    }

    public function testItFlushesAllMasterNodesAsync()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([
            ['127.0.0.1', '6379'],
            ['127.0.0.2', '6379'],
        ]);
        $client->expects('rawCommand')->with(['127.0.0.1', '6379'], 'flushdb', 'async');
        $client->expects('rawCommand')->with(['127.0.0.2', '6379'], 'flushdb', 'async');

        $connection = new PhpRedisClusterConnection($client);
        $connection->flushdb('ASYNC');
    }
}
