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
            ->with(0, ['127.0.0.1', '6379'], '*', 10)
            ->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
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

    public function testItScansEveryMasterInTurn()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379'], ['127.0.0.3', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')->with(0, $masters[0], '*', 10)->andReturn(['a']);
        $client->expects('scan')->with(0, $masters[1], '*', 10)->andReturn(['b']);
        $client->expects('scan')->with(0, $masters[2], '*', 10)->andReturn(['c']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor, $keys] = $connection->scan(0);
        $this->assertSame(['a'], $keys);

        [$cursor, $keys] = $connection->scan($cursor);
        $this->assertSame(['b'], $keys);

        $this->assertSame([0, ['c']], $connection->scan($cursor));
    }

    public function testItResumesAMasterFromTheEncodedCursor()
    {
        $masters = [['127.0.0.1', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')
            ->with(0, $masters[0], '*', 10)
            ->andReturnUsing(function (&$cursor) {
                $cursor = 42;

                return ['first'];
            });
        $client->expects('scan')
            ->with(42, $masters[0], '*', 10)
            ->andReturnUsing(function (&$cursor) {
                $cursor = 0;

                return ['last'];
            });

        $connection = new PhpRedisClusterConnection($client);

        [$cursor, $keys] = $connection->scan(0);

        $this->assertStringStartsWith('laravel:', $cursor);
        $this->assertSame(['first'], $keys);
        $this->assertSame([0, ['last']], $connection->scan($cursor));
    }

    public function testItKeepsScanningWhenAMasterReturnsNoKeys()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn($masters);
        $client->expects('scan')->with(0, $masters[0], '*', 10)->andReturn([]);
        $client->expects('scan')->with(0, $masters[1], '*', 10)->andReturn(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
    }

    public function testItPreservesLargeStringCursors()
    {
        $master = ['127.0.0.1', '6379'];
        $largeCursor = '18446744073709551615';

        $client = Mockery::mock(\RedisCluster::class);
        $client->allows('_masters')->andReturn([$master]);
        $client->expects('scan')
            ->with(null, $master, '*', 10)
            ->andReturnUsing(function (&$cursor) use ($largeCursor) {
                $cursor = $largeCursor;

                return ['first'];
            });
        $client->expects('scan')
            ->with($largeCursor, $master, '*', 10)
            ->andReturnUsing(function (&$cursor) {
                $cursor = '0';

                return ['last'];
            });

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(null);

        $this->assertSame([null, ['last']], $connection->scan($cursor));
    }

    public function testItKeepsNodeAffinityWhenMastersAreReordered()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->twice()->andReturn($masters, array_reverse($masters));
        $client->expects('scan')->with(0, $masters[0], '*', 10)->andReturn(['a']);
        $client->expects('scan')->with(0, $masters[1], '*', 10)->andReturn(['b']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(0);

        $this->assertSame([0, ['b']], $connection->scan($cursor));
    }

    public function testItContinuesWithAnotherMasterWhenTheCurrentMasterDisappears()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = Mockery::mock(\RedisCluster::class);
        $client->expects('_masters')->twice()->andReturn($masters, [$masters[1]]);
        $client->expects('scan')
            ->with(0, $masters[0], '*', 10)
            ->andReturnUsing(function (&$cursor) {
                $cursor = 42;

                return ['a'];
            });
        $client->expects('scan')->with(0, $masters[1], '*', 10)->andReturn(['b']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(0);

        $this->assertSame([0, ['b']], $connection->scan($cursor));
    }
}
