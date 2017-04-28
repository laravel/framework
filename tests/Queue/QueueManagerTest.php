<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\QueueManager;

class QueueManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDefaultConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.sync' => ['driver' => 'sync'],
            ],
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('sync')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });

        $queue->shouldReceive('setContainer')->once()->with($app);
        $this->assertSame($queue, $manager->connection('sync'));
    }

    public function testOtherConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.foo' => ['driver' => 'bar'],
            ],
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('foo')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'bar'])->andReturn($queue);
        $manager->addConnector('bar', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('foo'));
    }

    public function testNullConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'null',
            ],
            'encrypter' => $encrypter = m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock('StdClass');
        $queue = m::mock('StdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('null')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'null'])->andReturn($queue);
        $manager->addConnector('null', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('null'));
    }
}
