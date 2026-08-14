<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use Illuminate\Queue\QueueRoutes;
use PHPUnit\Framework\TestCase;

class QueueRoutesTest extends TestCase
{
    public function testSet()
    {
        $defaults = new QueueRoutes();

        $defaults->set(QueueRoutes::class, 'some-queue');
        $defaults->set(BaseNotification::class, 'some-queue', 'some-connection');

        $this->assertSame([
            QueueRoutes::class => [null, 'some-queue'],
            BaseNotification::class => ['some-connection', 'some-queue'],
        ], $defaults->all());

        // Ensure same class overrides
        $defaults->set([
            QueueRoutes::class => 'queue-many',
            SomeJob::class => 'important',
        ]);

        $this->assertSame([
            QueueRoutes::class => 'queue-many',
            BaseNotification::class => ['some-connection', 'some-queue'],
            SomeJob::class => 'important',
        ], $defaults->all()
        );
    }

    public function testGetQueue()
    {
        $defaults = new QueueRoutes();

        $defaults->set([
            BaseNotification::class => 'notifications',
            CustomTrait::class => 'jobs',
            PaymentContract::class => 'payments',
        ]);

        // No queue set
        $defaults->set(PaymentContract::class, connection: 'payment-connection');

        $this->assertSame('notifications', $defaults->getQueue(new FinanceNotification));
        $this->assertSame('jobs', $defaults->getQueue(new SomeJob));
        $this->assertNull($defaults->getQueue(new Payment));
    }

    public function testGetConnection()
    {
        $defaults = new QueueRoutes();

        $defaults->set([
            BaseNotification::class => ['notification-connection', 'notifications'],
            CustomTrait::class => ['job-connection', 'jobs'],
        ]);

        // No connection set
        $defaults->set(PaymentContract::class, 'payments');

        $this->assertSame('notification-connection', $defaults->getConnection(new FinanceNotification));
        $this->assertSame('job-connection', $defaults->getConnection(new SomeJob));
        $this->assertNull($defaults->getConnection(new Payment));
    }

    public function testStringRouteDefaultsToQueueNotConnection()
    {
        $defaults = new QueueRoutes();

        $defaults->set([BaseNotification::class => 'notifications']);

        $this->assertSame('notifications', $defaults->getQueue(new FinanceNotification));
        $this->assertNull($defaults->getConnection(new FinanceNotification));
    }

    public function testRouteQueueRewritesName()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue('reports', 'audit');

        $this->assertSame('audit', $defaults->resolveQueue('reports'));
        $this->assertSame('audit', $defaults->resolveQueue('reports', 'cloud'));
        $this->assertSame('other', $defaults->resolveQueue('other'));
    }

    public function testRouteQueueIsScopedToConnection()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue('reports', 'audit', 'cloud');

        $this->assertSame('audit', $defaults->resolveQueue('reports', 'cloud'));
        $this->assertSame('reports', $defaults->resolveQueue('reports', 'redis'));
        $this->assertSame('reports', $defaults->resolveQueue('reports'));
    }

    public function testRouteQueueWithoutDestinationKeepsName()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue('reports', connection: 'cloud');

        $this->assertSame('reports', $defaults->resolveQueue('reports', 'cloud'));
    }

    public function testRouteQueueRoutesConnectionByQueueName()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue('reports', 'audit', 'cloud');

        $this->assertSame('cloud', $defaults->getConnection((new SomeJob)->onQueue('reports')));
        $this->assertNull($defaults->getConnection((new SomeJob)->onQueue('other')));
        $this->assertNull($defaults->getConnection(new SomeJob));
    }

    public function testRouteQueueMatchesQueueAttribute()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue('reports', 'audit', 'cloud');

        $this->assertSame('cloud', $defaults->getConnection(new AttributeRoutedJob));
    }

    public function testRouteQueueResolvesEnums()
    {
        $defaults = new QueueRoutes();

        $defaults->setQueue(QueueName::payments, 'settlements', ConnectionName::redis);

        $this->assertSame('settlements', $defaults->resolveQueue('payments', 'redis'));
        $this->assertSame('payments', $defaults->resolveQueue('payments', 'sqs'));
    }

    public function testEnumsAreResolved()
    {
        $defaults = new QueueRoutes();

        $defaults->set(SomeJob::class, QueueName::payments, ConnectionName::redis);

        $this->assertSame('payments', $defaults->getQueue(new SomeJob));
        $this->assertSame('redis', $defaults->getConnection(new SomeJob));

        $defaults->set([SomeJob::class => [ConnectionName::redis, QueueName::payments]]);

        $this->assertSame('payments', $defaults->getQueue(new SomeJob));
        $this->assertSame('redis', $defaults->getConnection(new SomeJob));
    }
}

enum QueueName: string
{
    case payments = 'payments';
}

enum ConnectionName: string
{
    case redis = 'redis';
}

trait CustomTrait
{
}

class SomeJob
{
    use Queueable, CustomTrait;
}

#[QueueAttribute('reports')]
class AttributeRoutedJob
{
    use Queueable;
}

class BaseNotification
{
    use Queueable;
}

class FinanceNotification extends BaseNotification
{
}

interface PaymentContract
{
}

class Payment implements PaymentContract
{
}
