<?php

namespace Illuminate\Tests\Redis\Connections;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Tests\Redis\Fixtures\FakeRedisCluster;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PhpRedisClusterConnectionTest extends TestCase
{
    public function testItScansStartingFromTheFirstMaster()
    {
        $client = (new FakeRedisCluster([['127.0.0.1', '6379']]))->willScan(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
        $this->assertSame([[0, ['127.0.0.1', '6379'], '*', 10]], $client->scans);
    }

    public function testItScansUsingOptionNode()
    {
        $client = (new FakeRedisCluster([]))->willScan(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0, ['node' => 'option-node']));
        $this->assertSame([[0, 'option-node', '*', 10]], $client->scans);
        $this->assertSame(0, $client->mastersRequested);
    }

    public function testItThrowsExceptionWithoutNodes()
    {
        $client = new FakeRedisCluster([]);

        $this->expectExceptionObject(new InvalidArgumentException('No master nodes found in the cluster.'));

        try {
            (new PhpRedisClusterConnection($client))->scan(0);
        } finally {
            $this->assertSame([], $client->scans);
        }
    }

    public function testItReturnsFalseWhenCursorIsZeroAndResultIsEmpty()
    {
        $client = (new FakeRedisCluster([['127.0.0.1', '6379']]))->willScan(false);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertFalse($connection->scan(0));
        $this->assertSame([[0, ['127.0.0.1', '6379'], '*', 10]], $client->scans);
    }

    public function testItFlushesAllMasterNodes()
    {
        $client = new FakeRedisCluster([['127.0.0.1', '6379'], ['127.0.0.2', '6379']]);

        (new PhpRedisClusterConnection($client))->flushdb();

        $this->assertSame([
            ['flushdb', ['127.0.0.1', '6379']],
            ['flushdb', ['127.0.0.2', '6379']],
        ], $client->commands);
    }

    public function testItFlushesAllMasterNodesAsync()
    {
        $client = new FakeRedisCluster([['127.0.0.1', '6379'], ['127.0.0.2', '6379']]);

        (new PhpRedisClusterConnection($client))->flushdb('ASYNC');

        $this->assertSame([
            ['rawCommand', ['127.0.0.1', '6379'], 'flushdb', 'async'],
            ['rawCommand', ['127.0.0.2', '6379'], 'flushdb', 'async'],
        ], $client->commands);
    }

    public function testItScansEveryMasterInTurn()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379'], ['127.0.0.3', '6379']];

        $client = (new FakeRedisCluster($masters))->willScan(['a'])->willScan(['b'])->willScan(['c']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor, $keys] = $connection->scan(0);
        $this->assertSame(['a'], $keys);

        [$cursor, $keys] = $connection->scan($cursor);
        $this->assertSame(['b'], $keys);

        $this->assertSame([0, ['c']], $connection->scan($cursor));
        $this->assertSame(
            [$masters[0], $masters[1], $masters[2]],
            array_column($client->scans, 1)
        );
    }

    public function testItResumesAMasterFromTheEncodedCursor()
    {
        $masters = [['127.0.0.1', '6379']];

        $client = (new FakeRedisCluster($masters))->willScan(['first'], 42)->willScan(['last'], 0);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor, $keys] = $connection->scan(0);

        $this->assertStringStartsWith('laravel:', $cursor);
        $this->assertSame(['first'], $keys);
        $this->assertSame([0, ['last']], $connection->scan($cursor));
        $this->assertSame([0, 42], array_column($client->scans, 0));
    }

    public function testItKeepsScanningWhenAMasterReturnsNoKeys()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = (new FakeRedisCluster($masters))->willScan([])->willScan(['key']);

        $connection = new PhpRedisClusterConnection($client);
        $this->assertEquals([0, ['key']], $connection->scan(0));
        $this->assertSame([$masters[0], $masters[1]], array_column($client->scans, 1));
    }

    public function testItPreservesLargeStringCursors()
    {
        $master = ['127.0.0.1', '6379'];
        $largeCursor = '18446744073709551615';

        $client = (new FakeRedisCluster([$master]))->willScan(['first'], $largeCursor)->willScan(['last'], '0');

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(null);

        $this->assertSame([null, ['last']], $connection->scan($cursor));
        $this->assertSame([null, $largeCursor], array_column($client->scans, 0));
    }

    public function testItKeepsNodeAffinityWhenMastersAreReordered()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = (new FakeRedisCluster($masters, array_reverse($masters)))->willScan(['a'])->willScan(['b']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(0);

        $this->assertSame([0, ['b']], $connection->scan($cursor));
        $this->assertSame([$masters[0], $masters[1]], array_column($client->scans, 1));
        $this->assertSame(2, $client->mastersRequested);
    }

    public function testItContinuesWithAnotherMasterWhenTheCurrentMasterDisappears()
    {
        $masters = [['127.0.0.1', '6379'], ['127.0.0.2', '6379']];

        $client = (new FakeRedisCluster($masters, [$masters[1]]))->willScan(['a'], 42)->willScan(['b']);

        $connection = new PhpRedisClusterConnection($client);

        [$cursor] = $connection->scan(0);

        $this->assertSame([0, ['b']], $connection->scan($cursor));
        $this->assertSame([$masters[0], $masters[1]], array_column($client->scans, 1));
    }
}
