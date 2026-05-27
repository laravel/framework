<?php

namespace Illuminate\Tests\Integration\Console\Scheduling;

use Illuminate\Queue\Events\QueuePaused;
use Illuminate\Queue\Worker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class QueuePauseCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        Worker::$pausable = true;

        parent::tearDown();
    }

    public function testDispatchesEvent()
    {
        $this->artisan('queue:pause default');

        Event::assertDispatched(QueuePaused::class);
    }

    public function testDisabledError()
    {
        Worker::$pausable = false;

        $this->artisan('queue:pause default');

        Event::assertNotDispatched(QueuePaused::class);

        Worker::$pausable = true;
    }

    public function testCanPauseQueueForSeconds()
    {
        Carbon::setTestNow($now = Carbon::now());

        Event::fake();

        $this->artisan('queue:pause', [
            'queue' => 'default',
            '--seconds' => 30,
        ])->assertSuccessful();

        $this->assertTrue(Queue::isPaused(config('queue.default'), 'default'));

        Event::assertDispatched(QueuePaused::class, function ($event) {
            return $event->connection === config('queue.default')
                && $event->queue === 'default'
                && $event->ttl === 30;
        });

        Carbon::setTestNow($now->copy()->addSeconds(31));

        $this->assertFalse(Queue::isPaused(config('queue.default'), 'default'));
    }

    #[DataProvider('invalidSecondsOptions')]
    public function testSecondsOptionMustBePositive($seconds)
    {
        Event::fake();

        $this->artisan('queue:pause', [
            'queue' => 'default',
            '--seconds' => $seconds,
        ])->assertFailed();

        Event::assertNotDispatched(QueuePaused::class);
    }

    public static function invalidSecondsOptions()
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'float' => ['1.5'],
            'string' => ['invalid'],
        ];
    }
}
