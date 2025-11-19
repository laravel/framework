<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\QueueDefaults;
use Orchestra\Testbench\TestCase;

class QueueDefaultsTest extends TestCase
{
    public function testSet()
    {
        $defaults = new QueueDefaults();

        $defaults->set(QueueDefaults::class, 'some-queue');

        $this->assertSame([QueueDefaults::class => 'some-queue'], $defaults->all());

        $defaults->setMany([
            QueueDefaults::class => 'queue-many',
            'AClass' => 'mail',
        ]);

        $this->assertSame([QueueDefaults::class => 'queue-many', 'AClass' => 'mail'], $defaults->all());
    }

    public function testGet()
    {
        $defaults = new QueueDefaults();

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
