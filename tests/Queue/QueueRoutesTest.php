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

    public function testForwardRewritesName()
    {
        $defaults = new QueueRoutes();

        $defaults->forward('reports', 'audit');

        $this->assertSame('audit', $defaults->forwardedQueue('reports'));
        $this->assertSame('audit', $defaults->forwardedQueue('reports', 'cloud'));
        $this->assertSame('other', $defaults->forwardedQueue('other'));
    }

    public function testForwardIsScopedToConnection()
    {
        $defaults = new QueueRoutes();

        $defaults->forward('reports', 'audit', 'cloud');

        $this->assertSame('audit', $defaults->forwardedQueue('reports', 'cloud'));
        $this->assertSame('reports', $defaults->forwardedQueue('reports', 'redis'));
        $this->assertSame('reports', $defaults->forwardedQueue('reports'));
    }

    public function testForwardWithJustConnectionKeepsName()
    {
        $defaults = new QueueRoutes();

        $defaults->forward('reports', connection: 'cloud');

        $this->assertSame('reports', $defaults->forwardedQueue('reports', 'cloud'));
    }

    public function testForwardSetsConnectionByQueueName()
    {
        $defaults = new QueueRoutes();

        $defaults->forward('reports', 'audit', 'cloud');

        $this->assertSame('cloud', $defaults->getConnection((new SomeJob)->onQueue('reports')));
        $this->assertNull($defaults->getConnection((new SomeJob)->onQueue('other')));
        $this->assertNull($defaults->getConnection(new SomeJob));
    }

    public function testForwardMatchesQueueAttribute()
    {
        $defaults = new QueueRoutes();

        $defaults->forward('reports', 'audit', 'cloud');

        $this->assertSame('cloud', $defaults->getConnection(new AttributeForwardedJob));
    }

    public function testForwardAcceptsArray()
    {
        $defaults = new QueueRoutes();

        $defaults->forward([
            'reports' => 'audit',
            'emails' => 'mail',
        ], connection: 'cloud');

        $this->assertSame('audit', $defaults->forwardedQueue('reports', 'cloud'));
        $this->assertSame('mail', $defaults->forwardedQueue('emails', 'cloud'));
        $this->assertSame('reports', $defaults->forwardedQueue('reports', 'redis'));
    }

    public function testForwardResolvesEnums()
    {
        $defaults = new QueueRoutes();

        $defaults->forward(QueueName::payments, 'settlements', ConnectionName::redis);

        $this->assertSame('settlements', $defaults->forwardedQueue('payments', 'redis'));
        $this->assertSame('payments', $defaults->forwardedQueue('payments', 'sqs'));
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
class AttributeForwardedJob
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
