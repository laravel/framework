<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\QueueManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueManagerTest extends TestCase
{
    protected function tearDown(): void
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
            'encrypter' => $encrypter = m::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock(stdClass::class);
        $queue = m::mock(stdClass::class);
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
            'encrypter' => $encrypter = m::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock(stdClass::class);
        $queue = m::mock(stdClass::class);
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
            'encrypter' => $encrypter = m::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = m::mock(stdClass::class);
        $queue = m::mock(stdClass::class);
        $queue->shouldReceive('setConnectionName')->once()->with('null')->andReturnSelf();
        $connector->shouldReceive('connect')->once()->with(['driver' => 'null'])->andReturn($queue);
        $manager->addConnector('null', function () use ($connector) {
            return $connector;
        });
        $queue->shouldReceive('setContainer')->once()->with($app);

        $this->assertSame($queue, $manager->connection('null'));
    }
}
