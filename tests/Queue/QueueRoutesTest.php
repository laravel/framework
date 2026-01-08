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

        $this->assertSame([QueueRoutes::class => [null, 'some-queue']], $defaults->all());

        $defaults->set([
            QueueRoutes::class => 'queue-many',
            SomeJob::class => 'important',
        ]);

        $this->assertSame([QueueRoutes::class => 'queue-many', SomeJob::class => 'important'], $defaults->all());
    }

    public function testGetQueue()
    {
        $defaults = new QueueRoutes();

        $defaults->set([
            BaseNotification::class => 'notifications',
            CustomTrait::class => 'jobs',
            PaymentContract::class => 'payments',
        ]);

        $this->assertSame('notifications', $defaults->getQueue(new FinanceNotification));
        $this->assertSame('jobs', $defaults->getQueue(new SomeJob));
        $this->assertSame('payments', $defaults->getQueue(new Payment));
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
