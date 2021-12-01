<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Bus\Queueable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\TestCase;

class CallbackSchedulingTest extends TestCase
{
    protected $log = [];

    /**
     * @dataProvider executionProvider
     */
    public function testExecutionOrder($background)
    {
        $event = $this->app->make(Schedule::class)
            ->call($this->logger('call'))
            ->after($this->logger('after 1'))
            ->before($this->logger('before 1'))
            ->after($this->logger('after 2'))
            ->before($this->logger('before 2'));

        if ($background) {
            $event->runInBackground();
        }

        $this->artisan('schedule:run');

        $this->assertLogged('before 1', 'before 2', 'call', 'after 1', 'after 2');
    }

    public function executionProvider()
    {
        return [
            'Foreground' => [false],
            'Background' => [true],
        ];
    }

    protected function logger($message)
    {
        return function () use ($message) {
            $this->log[] = $message;
        };
    }

    protected function assertLogged(...$message)
    {
        $this->assertEquals($message, $this->log);
    }
}
