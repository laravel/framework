<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('redis')]
class PhpRedisClusterConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testItScansUsingDefaultNode()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([['127.0.0.1', '6379']]);
        $client->shouldReceive('scan')
            ->once()
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
    }

    public function testItOnlyFetchesDefaultNodeOnce()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([['127.0.0.1', '6379']]);
        $client->shouldReceive('scan')->twice();

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
        $connection->scan(0);
    }

    public function testItScansUsingOptionNode()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('scan')
            ->once()
            ->with(0, 'option-node', '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0, ['node' => 'option-node']));
    }

    public function testItThrowsExceptionWithoutNodes()
    {
        $client = m::mock(\RedisCluster::class);
        $client->shouldReceive('_masters')->once()->andReturn([]);
        $client->shouldReceive('scan');

        $this->expectExceptionMessage('Unable to determine default node. No master nodes found in the cluster.');

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
    }
}
