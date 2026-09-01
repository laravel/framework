<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\QueueManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class QueueManagerTest extends TestCase
{
    public function testDefaultConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.sync' => ['driver' => 'sync'],
            ],
            'encrypter' => $encrypter = Mockery::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = Mockery::mock(ConnectorInterface::class);
        $queue = Mockery::mock(stdClass::class);
        $queue->expects('setConnectionName')->with('sync')->andReturnSelf();
        $connector->expects('connect')->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });

        $queue->expects('setContainer')->with($app);
        $this->assertSame($queue, $manager->connection('sync'));
    }

    public function testOtherConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.foo' => ['driver' => 'bar'],
            ],
            'encrypter' => $encrypter = Mockery::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = Mockery::mock(ConnectorInterface::class);
        $queue = Mockery::mock(stdClass::class);
        $queue->expects('setConnectionName')->with('foo')->andReturnSelf();
        $connector->expects('connect')->with(['driver' => 'bar'])->andReturn($queue);
        $manager->addConnector('bar', function () use ($connector) {
            return $connector;
        });
        $queue->expects('setContainer')->with($app);

        $this->assertSame($queue, $manager->connection('foo'));
    }

    public function testNullConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'null',
            ],
            'encrypter' => $encrypter = Mockery::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = Mockery::mock(ConnectorInterface::class);
        $queue = Mockery::mock(stdClass::class);
        $queue->expects('setConnectionName')->with('null')->andReturnSelf();
        $connector->expects('connect')->with(['driver' => 'null'])->andReturn($queue);
        $manager->addConnector('null', function () use ($connector) {
            return $connector;
        });
        $queue->expects('setContainer')->with($app);

        $this->assertSame($queue, $manager->connection('null'));
    }

    public function testEnumConnectionCanBeResolved()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.sync' => ['driver' => 'sync'],
            ],
            'encrypter' => $encrypter = Mockery::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = Mockery::mock(ConnectorInterface::class);
        $queue = Mockery::mock(stdClass::class);
        $queue->expects('setConnectionName')->with('sync')->andReturnSelf();
        $connector->expects('connect')->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });
        $queue->expects('setContainer')->with($app);

        $this->assertSame($queue, $manager->connection(QueueConnectionName::Sync));
    }

    public function testEnumConnectionCanBeChecked()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.sync' => ['driver' => 'sync'],
            ],
            'encrypter' => $encrypter = Mockery::mock(Encrypter::class),
        ];

        $manager = new QueueManager($app);
        $connector = Mockery::mock(ConnectorInterface::class);
        $queue = Mockery::mock(stdClass::class);
        $queue->expects('setConnectionName')->with('sync')->andReturnSelf();
        $connector->expects('connect')->with(['driver' => 'sync'])->andReturn($queue);
        $manager->addConnector('sync', function () use ($connector) {
            return $connector;
        });
        $queue->expects('setContainer')->with($app);

        $this->assertFalse($manager->connected(QueueConnectionName::Sync));
        $manager->connection(QueueConnectionName::Sync);
        $this->assertTrue($manager->connected(QueueConnectionName::Sync));
    }

    public function testSetDefaultDriverAcceptsBackedEnum()
    {
        $app = [
            'config' => [
                'queue.default' => 'sync',
                'queue.connections.sync' => ['driver' => 'sync'],
            ],
        ];

        $manager = new QueueManager($app);
        $manager->setDefaultDriver(QueueConnectionName::Sync);

        $this->assertSame('sync', $app['config']['queue.default']);
    }
}

enum QueueConnectionName: string
{
    case Sync = 'sync';
}
