<?php

namespace Illuminate\Tests\Queue;

use stdClass;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Config\Repository;
use Illuminate\Queue\QueueManager;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Encryption\Encrypter;

class QueueManagerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testDefaultConnectionCanBeResolved()
    {
        $app = new Application();

        $config = new Repository();
        $config->set('queue.default', 'sync');
        $config->set('queue.connections.sync', ['driver' => 'sync']);

        $app->instance('config', $config);
        $app->instance('encrypter', $encrypter = m::mock(Encrypter::class));

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
        $app = new Application();

        $config = new Repository();
        $config->set('queue.default', 'sync');
        $config->set('queue.connections.foo', ['driver' => 'bar']);

        $app->instance('config', $config);
        $app->instance('encrypter', $encrypter = m::mock(Encrypter::class));

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
