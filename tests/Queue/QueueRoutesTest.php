<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Queue\Queueable;
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
}

trait CustomTrait
{
}

class SomeJob
{
    use Queueable, CustomTrait;
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
