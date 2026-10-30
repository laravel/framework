<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Testing\Fakes\QueueFake;
use Orchestra\Testbench\TestCase;

class QueueFakeTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('queue.default', 'sync');
    }

    public function testFakeFor()
    {
        Queue::fakeFor(function () {
            Queue::push(new QueueFakeTestJob);
            Queue::assertPushed(QueueFakeTestJob::class);
        });
    }

    public function testFakeExceptFor()
    {
        Queue::fakeExceptFor(function () {
            Queue::push(new QueueFakeTestJob);
            Queue::push(new QueueFakeOtherTestJob);

            Queue::assertNotPushed(QueueFakeTestJob::class);
            Queue::assertPushed(QueueFakeOtherTestJob::class);
        }, [QueueFakeTestJob::class]);
    }

    public function testFakeExcept()
    {
        $fake = Queue::fakeExcept([QueueFakeTestJob::class]);

        $this->assertInstanceOf(QueueFake::class, $fake);
    }

    public function testFakeForReturnValue()
    {
        $result = Queue::fakeFor(function () {
            return 'test-value';
        });

        $this->assertSame('test-value', $result);
    }

    public function testFakeExceptForReturnValue()
    {
        $result = Queue::fakeExceptFor(function () {
            return 'test-value';
        }, []);

        $this->assertSame('test-value', $result);
    }
}

class QueueFakeTestJob
{
    use Queueable;

    public function handle()
    {
        //
    }
}

class QueueFakeOtherTestJob
{
    use Queueable;

    public function handle()
    {
        //
    }
}
