<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\QueueRoutes;
use Orchestra\Testbench\TestCase;

class QueueRoutesTest extends TestCase
{
    public function testSet()
    {
        $defaults = new QueueRoutes();

        $defaults->set(QueueRoutes::class, 'some-queue');

        $this->assertSame([QueueRoutes::class => 'some-queue'], $defaults->all());

        $defaults->setMany([
            QueueRoutes::class => 'queue-many',
            'AClass' => 'mail',
        ]);

        $this->assertSame([QueueRoutes::class => 'queue-many', 'AClass' => 'mail'], $defaults->all());
    }

    public function testGet()
    {
        $defaults = new QueueRoutes();

        $defaults->setMany([
            BaseNotification::class => 'notifications',
            CustomTrait::class => 'jobs',
            PaymentContract::class => 'payments',
        ]);

        $this->assertSame('notifications', $defaults->get(new FinanceNotification));
        $this->assertSame('jobs', $defaults->get(new SomeJob));
        $this->assertSame('payments', $defaults->get(new Payment));
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
