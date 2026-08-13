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
    public function testItScansStartingFromTheFirstMaster()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([['127.0.0.1', '6379']]);
        $client->expects('scan')
            ->with(null, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals(['1:', ['key']], $connection->scan(0));
    }

    public function testItScansEveryMasterInTurn()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379'], ['127.0.0.3', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')->with(null, $masters[0], '*', 10)->andReturn(['a']);
        $client->expects('scan')->with(null, $masters[1], '*', 10)->andReturn(['b']);
        $client->expects('scan')->with(null, $masters[2], '*', 10)->andReturn(['c']);

        $connection = new PhpRedisClusterConnection($client);

        $this->assertEquals(['1:', ['a']], $connection->scan(0));
        $this->assertEquals(['2:', ['b']], $connection->scan('1:'));
        $this->assertEquals(['3:', ['c']], $connection->scan('2:'));
        $this->assertFalse($connection->scan('3:'));
    }

    public function testItResumesAMasterFromTheEncodedCursor()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')
            ->with(42, $masters[1], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals(['1:42', ['key']], $connection->scan('1:42'));
    }

    public function testItKeepsScanningWhenAMasterReturnsNoKeys()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')->with(null, $masters[0], '*', 10)->andReturn([]);
        $client->expects('scan')->with(null, $masters[1], '*', 10)->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals(['2:', ['key']], $connection->scan(0));
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

        $this->expectExceptionObject(new InvalidArgumentException('No master nodes found in the cluster.'));

        $connection = new PhpRedisClusterConnection($client);
        $connection->scan(0);
    }

    public function testItReturnsFalseWhenCursorIsZeroAndResultIsEmpty()
    {
        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->andReturn([['127.0.0.1', '6379']]);
        $client->expects('scan')
            ->with(null, ['127.0.0.1', '6379'], '*', 10)
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
