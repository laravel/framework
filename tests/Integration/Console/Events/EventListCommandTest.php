<?php

namespace Illuminate\Tests\Integration\Console\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Console\EventListCommand;
use Orchestra\Testbench\TestCase;

class EventListCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new Dispatcher();
        EventListCommand::resolveEventsUsing(fn () => $this->dispatcher);
    }

    public function testDisplayEmptyList()
    {
        $this->artisan(EventListCommand::class)
            ->assertSuccessful()
            ->expectsOutput("Your application doesn't have any events matching the given criteria.");
    }

    public function testDisplayEvents()
    {
        $this->dispatcher->subscribe(ExampleSubscriber::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleListener::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleQueueListener::class);
        $this->dispatcher->listen(ExampleBroadcastEvent::class, ExampleBroadcastListener::class);
        $this->dispatcher->listen(ExampleEvent::class, fn () => '');
        $closureLineNumber = __LINE__ - 1;
        $unixFilePath = str_replace('\\', '/', __FILE__);

        $this->artisan(EventListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('  ExampleSubscriberEventName')
            ->expectsOutput('    ⇂ Illuminate\Tests\Integration\Console\Events\ExampleSubscriber@a')
            ->expectsOutput('    ⇂ Illuminate\Tests\Integration\Console\Events\ExampleSubscriber@b')
            ->expectsOutput('  Illuminate\Tests\Integration\Console\Events\ExampleBroadcastEvent (ShouldBroadcast)')
            ->expectsOutput('    ⇂ Illuminate\Tests\Integration\Console\Events\ExampleBroadcastListener')
            ->expectsOutput('  Illuminate\Tests\Integration\Console\Events\ExampleEvent')
            ->expectsOutput('    ⇂ Illuminate\Tests\Integration\Console\Events\ExampleListener')
            ->expectsOutput('    ⇂ Illuminate\Tests\Integration\Console\Events\ExampleQueueListener (ShouldQueue)')
            ->expectsOutput('    ⇂ Closure at: '.$unixFilePath.':'.$closureLineNumber);
    }

    public function testDisplayFilteredEvent()
    {
        $this->dispatcher->subscribe(ExampleSubscriber::class);
        $this->dispatcher->listen(ExampleEvent::class, ExampleListener::class);

        $this->artisan(EventListCommand::class, ['--event' => 'ExampleEvent'])
            ->assertSuccessful()
            ->doesntExpectOutput('  ExampleSubscriberEventName')
            ->expectsOutputToContain('ExampleEvent');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        EventListCommand::resolveEventsUsing(null);
    }
}

class ExampleSubscriber
{
    public function subscribe()
    {
        return [
            'ExampleSubscriberEventName' => [
                self::class.'@a',
                self::class.'@b',
            ],
        ];
    }

    public function a()
    {
    }

    public function b()
    {
    }
}

class ExampleEvent
{
}

class ExampleBroadcastEvent implements ShouldBroadcast
{
    public function broadcastOn()
    {
        //
    }
}

class ExampleListener
{
    public function handle()
    {
    }
}

class ExampleQueueListener implements ShouldQueue
{
    public function handle()
    {
    }
}

class ExampleBroadcastListener
{
    public function handle()
    {
        //
    }
}
