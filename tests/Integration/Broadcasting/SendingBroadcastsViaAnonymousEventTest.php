<?php

namespace Illuminate\Tests\Integration\Broadcasting;

use Illuminate\Broadcasting\AnonymousEvent;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast as BroadcastFacade;
use Illuminate\Support\Facades\Event as EventFacade;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class SendingBroadcastsViaAnonymousEventTest extends TestCase
{
    public function testBroadcastIsSent()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->with(['some' => 'data'])
            ->as('test-event')
            ->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return (new ReflectionClass($event))->getProperty('connection')->getValue($event) === null &&
                $event->broadcastOn() === ['test-channel'] &&
                $event->broadcastAs() === 'test-event' &&
                $event->broadcastWith() === ['some' => 'data'];
        });
    }

    public function testBroadcastIsSentNow()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->with(['some' => 'data'])
            ->as('test-event')
            ->sendNow();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return (new ReflectionClass($event))->getProperty('connection')->getValue($event) === null &&
                $event->shouldBroadcastNow();
        });
    }

    public function testDefaultNameIsSet()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')
            ->with(['some' => 'data'])
            ->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return $event->broadcastAs() === 'AnonymousEvent';
        });
    }

    public function testDefaultPayloadIsSet()
    {
        EventFacade::fake();

        BroadcastFacade::on('test-channel')->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
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

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
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

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return (new ReflectionClass($event))->getProperty('connection')->getValue($event) === 'pusher';
        });
    }

    public function testSendToOthersOnly()
    {
        EventFacade::fake();

        $this->app['request']->headers->add(['X-Socket-ID' => '12345']);

        BroadcastFacade::on('test-channel')->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return $event->socket === null;
        });

        BroadcastFacade::on('test-channel')
            ->toOthers()
            ->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            return $event->socket === '12345';
        });
    }

    public function testSendToPrivateChannel()
    {
        EventFacade::fake();

        BroadcastFacade::private('test-channel')->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            $channel = $event->broadcastOn()[0];

            return $channel instanceof PrivateChannel && $channel->name === 'private-test-channel';
        });
    }

    public function testSendToPresenceChannel()
    {
        EventFacade::fake();

        BroadcastFacade::presence('test-channel')->send();

        EventFacade::assertDispatched(AnonymousEvent::class, function ($event) {
            $channel = $event->broadcastOn()[0];

            return $channel instanceof PresenceChannel && $channel->name === 'presence-test-channel';
        });
    }
}
