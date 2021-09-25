<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\NotificationSender;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class NotificationSenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testItCanSendQueuedNotificationsWithAStringVia()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldReceive('dispatch');
        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithStringVia);
    }

    public function testItCanSendNotificationsWithAnEmptyStringVia()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldNotReceive('dispatch');
        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithEmptyStringVia);
    }

    public function testItCannotSendNotificationsViaDatabaseForAnonymousNotifiables()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldNotReceive('dispatch');
        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithDatabaseVia);
    }
}

class DummyQueuedNotificationWithStringVia extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return 'mail';
    }
}

class DummyNotificationWithEmptyStringVia extends Notification
{
    use Queueable;

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return '';
    }
}

class DummyNotificationWithDatabaseVia extends Notification
{
    use Queueable;

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return 'database';
    }
}
