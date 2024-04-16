<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\AnonymousBroadcastable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast as BroadcastFacade;
use Illuminate\Support\Facades\Event as EventFacade;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class SendingBroadcastsViaAnonymousBroadcastableTest extends TestCase
{
    public function testBroadcastIsSent()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->with(['some' => 'data'])
            ->as('test-event')
            ->send();

        EventFacade::assertDispatched(AnonymousBroadcastable::class, function ($event) {
            return (new ReflectionClass($event))->getProperty('connection')->getValue($event) === null &&
                $event->broadcastOn() === ['test-channel'] &&
                $event->broadcastAs() === 'test-event' &&
                $event->broadcastWith() === ['some' => 'data'];
        });
    }

    public function testDefaultNameIsSet()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->with(['some' => 'data'])
            ->send();

        EventFacade::assertDispatched(AnonymousBroadcastable::class, function ($event) {
            return $event->broadcastAs() === 'AnonymousBroadcastable';
        });
    }

    public function testDefaultPayloadIsSet()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')->send();

        EventFacade::assertDispatched(AnonymousBroadcastable::class, function ($event) {
            return $event->broadcastWith() === [];
        });
    }

    public function testSendToMultipleChannels()
    {
        EventFacade::fake();

        BroadcastFacade::on([
            'test-channel',
            new PrivateChannel('test-channel'),
            'presence-test-channel',
        ])->send();

        EventFacade::assertDispatched(AnonymousBroadcastable::class, function ($event) {
            [$one, $two, $three] = $event->broadcastOn();

            return $one === 'test-channel' &&
                $two instanceof PrivateChannel &&
                $two->name === 'private-test-channel' &&
                $three === 'presence-test-channel';
        });
    }

    public function testSendViaANonDefaultConnection()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->via('pusher')
            ->send();

        EventFacade::assertDispatched(AnonymousBroadcastable::class, function ($event) {
            return (new ReflectionClass($event))->getProperty('connection')->getValue($event) === 'pusher';
        });
    }
}
