<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Container\Container;
use Illuminate\Encryption\Encrypter;
use Illuminate\Queue\Connectors\SyncConnector;
use Illuminate\Queue\Queue;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class QueueManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Queue::createPayloadUsing(null);
    }

    public function testDefaultConnectionCanBeResolved()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'sync',
            'queue.connections.sync' => ['driver' => 'sync'],
        ];
        $app['encrypter'] = new Encrypter(str_repeat('a', 16));

        $manager = new QueueManager($app);
        $manager->addConnector('sync', fn () => new SyncConnector);

        $queue = $manager->connection('sync');

        $this->assertInstanceOf(SyncQueue::class, $queue);
        $this->assertSame('sync', $queue->getConnectionName());
        $this->assertSame($app, $queue->getContainer());
    }

    public function testOtherConnectionCanBeResolved()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'sync',
            'queue.connections.foo' => ['driver' => 'bar'],
        ];
        $app['encrypter'] = new Encrypter(str_repeat('a', 16));

        $manager = new QueueManager($app);
        $manager->addConnector('bar', fn () => new SyncConnector);

        $queue = $manager->connection('foo');

        $this->assertInstanceOf(SyncQueue::class, $queue);
        $this->assertSame('foo', $queue->getConnectionName());
        $this->assertSame($app, $queue->getContainer());
    }

    public function testNullConnectionCanBeResolved()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'null',
        ];
        $app['encrypter'] = new Encrypter(str_repeat('a', 16));

        $manager = new QueueManager($app);
        $manager->addConnector('null', fn () => new SyncConnector);

        $queue = $manager->connection('null');

        $this->assertInstanceOf(SyncQueue::class, $queue);
        $this->assertSame('null', $queue->getConnectionName());
        $this->assertSame($app, $queue->getContainer());
    }

    public function testEnumConnectionCanBeResolved()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'sync',
            'queue.connections.sync' => ['driver' => 'sync'],
        ];
        $app['encrypter'] = new Encrypter(str_repeat('a', 16));

        $manager = new QueueManager($app);
        $manager->addConnector('sync', fn () => new SyncConnector);

        $queue = $manager->connection(QueueConnectionName::Sync);

        $this->assertInstanceOf(SyncQueue::class, $queue);
        $this->assertSame('sync', $queue->getConnectionName());
        $this->assertSame($app, $queue->getContainer());
    }

    public function testEnumConnectionCanBeChecked()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'sync',
            'queue.connections.sync' => ['driver' => 'sync'],
        ];
        $app['encrypter'] = new Encrypter(str_repeat('a', 16));

        $manager = new QueueManager($app);
        $manager->addConnector('sync', fn () => new SyncConnector);

        $this->assertFalse($manager->connected(QueueConnectionName::Sync));
        $manager->connection(QueueConnectionName::Sync);
        $this->assertTrue($manager->connected(QueueConnectionName::Sync));
    }

    public function testCreatePayloadUsingDoesNotResolveTheDefaultConnection()
    {
        $app = new Container;
        $app['config'] = [
            'queue.default' => 'cloud',
        ];

        $manager = new QueueManager($app);

        $manager->createPayloadUsing(function ($connection, $queue, $payload) {
            return ['foo' => 'bar'];
        });

        $this->assertFalse($manager->connected('cloud'));

        $callbacks = (new ReflectionProperty(Queue::class, 'createPayloadCallbacks'))->getValue();

        $this->assertSame(['foo' => 'bar'], $callbacks[array_key_last($callbacks)](null, null, null));
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
