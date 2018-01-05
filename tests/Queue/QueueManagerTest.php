<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\SharedData;
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
        $shared = new SharedData();

        $manager = new QueueManager($app, $shared);
        $connector = m::mock('stdClass');
        $queue = m::mock('stdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('sync')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });

        $queue->shouldReceive('setContainer')->once()->with($app);
        $queue->shouldReceive('setShared')->once()->with($shared);
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
        $shared = new SharedData();

        $manager = new QueueManager($app, $shared);
        $connector = m::mock('stdClass');
        $queue = m::mock('stdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('foo')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'bar'])->andReturn($queue);
        $manager->addConnector('bar', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);
        $queue->shouldReceive('setShared')->once()->with($shared);

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
        $shared = new SharedData();

        $manager = new QueueManager($app, $shared);
        $connector = m::mock('stdClass');
        $queue = m::mock('stdClass');
        $queue->shouldReceive('setConnectionName')->once()->with('null')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'null'])->andReturn($queue);
        $manager->addConnector('null', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);
        $queue->shouldReceive('setShared')->once()->with($shared);

        $this->assertSame($queue, $manager->connection('null'));
    }

    public function testShareIsHandledCorrectly()
    {
        $app = [
            'config' => [
                'queue.default' => 'null',
            ],
            'encrypter' => m::mock('Illuminate\Contracts\Encryption\Encrypter'),
        ];
        $shared = new SharedData();

        $manager = new QueueManager($app, $shared);
        $this->assertInstanceOf(\Illuminate\Queue\SharedData::class, $manager->share(null));

        $manager->share('foo', 'bar');
        $this->assertSame(['foo' => 'bar'], $shared->toArray());

        $manager->share(['foo' => 'bart', 'bar' => 'foo']);
        $this->assertSame(['foo' => 'bart', 'bar' => 'foo'], $shared->toArray());
    }
}
