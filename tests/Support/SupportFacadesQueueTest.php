<?php

namespace Illuminate\Tests\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Queue\Queue as QueueInterface;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class SupportFacadesQueueTest extends TestCase
{
    use VerifiesDoubles;

    private $queueManager;

    protected function setUp(): void
    {
        $this->queueManager = Double::for(QueueContract::class, QueueInterface::class);

        $container = new Container;
        $container->instance('queue', $this->queueManager);
        $container->alias('queue', QueueContract::class);

        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Queue::clearResolvedInstance();
        Queue::setFacadeApplication(null);
    }

    public function testFakeFor()
    {
        Queue::fakeFor(function () {
            (new QueueForStub)->pushJob();

            Queue::assertPushed(QueueJobStub::class);
        });

        $this->queueManager->expects('push');

        (new QueueForStub)->pushJob();
    }

    public function testFakeForSwapsQueueManager()
    {
        Queue::fakeFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        });

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExcept()
    {
        $fake = Queue::fakeExcept(QueueJobStub::class);

        $this->assertInstanceOf(QueueFake::class, $fake);
        $this->assertSame($fake, Queue::getFacadeRoot());
    }

    public function testFakeExceptFor()
    {
        Queue::fakeExceptFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        }, [QueueJobStub::class]);

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExceptForSwapsQueueManager()
    {
        Queue::fakeExceptFor(function () {
            $this->assertInstanceOf(QueueFake::class, Queue::getFacadeRoot());
        }, []);

        $this->assertSame($this->queueManager, Queue::getFacadeRoot());
    }

    public function testFakeExceptForReturnValue()
    {
        $result = Queue::fakeExceptFor(function () {
            return 'test-result';
        });

        $this->assertSame('test-result', $result);
    }

    public function testFakeForReturnValue()
    {
        $result = Queue::fakeFor(function () {
            return 'test-result';
        });

        $this->assertSame('test-result', $result);
    }
}

class QueueJobStub
{
    use Queueable;
}

class OtherQueueJobStub
{
    use Queueable;
}

class QueueForStub
{
    public function pushJob()
    {
        Queue::push(new QueueJobStub);
    }
}
