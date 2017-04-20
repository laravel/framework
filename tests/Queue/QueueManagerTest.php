<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Queue\QueueManager;

class QueueManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDefaultConnectionCanBeResolved()
    {
        $config = new Repository([
            'queue.default' => 'sync',
            'queue.connections.sync' => ['driver' => 'sync'],
        ]);

        $app = [
            'config' => $config,
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('sync')->andReturnSelf();
        $queue->shouldReceive('setQueuePrefix')->once()->with('')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('sync'));
    }

    public function testOtherConnectionCanBeResolved()
    {
        $config = new Repository([
            'queue.default' => 'sync',
            'queue.connections.foo' => ['driver' => 'bar'],
        ]);

        $app = [
            'config' => $config,
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('foo')->andReturnSelf();
        $queue->shouldReceive('setQueuePrefix')->once()->with('')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'bar'])->andReturn($queue);
        $manager->addConnector('bar', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('foo'));
    }

    public function testNullConnectionCanBeResolved()
    {
        $config = new Repository([
            'queue.default' => 'null',
        ]);

        $app = [
            'config' => $config,
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('null')->andReturnSelf();
        $queue->shouldReceive('setQueuePrefix')->once()->with('')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'null'])->andReturn($queue);
        $manager->addConnector('null', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('null'));
    }
}
