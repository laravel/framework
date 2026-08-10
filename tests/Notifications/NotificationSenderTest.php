<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\NotificationSender;
use Illuminate\Queue\Attributes\Queue;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NotificationSenderTest extends TestCase
{
    public function test_it_can_send_queued_notifications_with_a_string_via()
    {
        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveQueueFromQueueRoute')->andReturn(null);
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn(null);
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch');
        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithStringVia);
    }

    public function test_it_can_send_queued_notifications_with_an_array_via()
    {
        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->times(2)->andReturn(app());
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'dummy' && $job->channels === ['database'] && $job->connection === 'redis';
            });
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'dummy' && $job->channels === ['mail'] && $job->connection === 'redis';
            });

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithArrayVia);
    }

    public function test_it_can_send_notifications_with_an_empty_string_via()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->shouldNotReceive('dispatch');
        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithEmptyStringVia);
    }

    public function test_it_cannot_send_notifications_via_database_for_anonymous_notifiables()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->shouldNotReceive('dispatch');
        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithDatabaseVia);
    }

    public function test_it_can_send_queued_notifications_through_middleware()
    {
        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestNotificationMiddleware;
            });
        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveQueueFromQueueRoute')->andReturn(null);
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn(null);

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithMiddleware);
    }

    public function test_it_can_send_queued_multi_channel_notifications_through_different_middleware()
    {
        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->times(3)->andReturn(app());
        $manager->expects('resolveQueueFromQueueRoute')->times(3)->andReturn(null);
        $manager->expects('resolveConnectionFromQueueRoute')->times(3)->andReturn(null);
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestMailNotificationMiddleware;
            });
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestDatabaseNotificationMiddleware;
            });
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return empty($job->middleware);
            });
        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyMultiChannelNotificationWithConditionalMiddleware);
    }

    public function test_it_can_send_queued_with_via_connections_notifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->times(2)->andReturn(app());
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->connection === 'sync' && $job->channels === ['database'] && $job->queue === 'dummy';
            });
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->connection === 'redis' && $job->channels === ['mail'] && $job->queue === 'dummy';
            });

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaConnections);
    }

    public function test_it_can_send_queued_with_via_queues_notifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->times(2)->andReturn(app());
        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'dummy' && $job->channels === ['database'] && $job->connection === 'redis';
            });
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'admin_notifications' && $job->channels === ['mail'] && $job->connection === 'redis';
            });

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaQueues);
    }

    public function test_it_can_send_queued_notifications_with_queue_route()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveQueueFromQueueRoute')->andReturn('notification-queue');
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn('notification-connection');

        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'notification-queue' && $job->channels === ['mail'] && $job->connection === 'notification-connection';
            });

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithStringVia);
    }

    public function test_notification_failed_sent_without_http_transport_exception()
    {
        $this->expectException(TransportException::class);

        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $response = Mockery::mock(ResponseInterface::class);
        $driver->expects('send')->andThrow(new HttpTransportException('Transport error', $response));
        $bus = Mockery::mock(BusDispatcher::class);

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $events->expects('dispatch')->withArgs(function ($event) {
            return $event instanceof NotificationFailed && $event->data['exception'] instanceof TransportException;
        });

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithViaConnections, ['mail']);
    }

    public function test_it_preserves_notification_state_mutated_in_via_method()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = Mockery::mock(ChannelManager::class);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $driver->expects('send')->withArgs(function ($notifiable, $notification) {
            return $notification->channelData === 'default';
        });
        $bus = Mockery::mock(BusDispatcher::class);

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $events->expects('dispatch');

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithViaMutation);
    }

    public function test_it_queue_overrides_queue_attribute()
    {
        $notification = new #[Queue('attribute-queue')] class extends Notification implements ShouldQueue
        {
            use Queueable;

            public function via($notifiable): string
            {
                return 'mail';
            }
        };

        $notification->onQueue('manual-queue');

        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn(null);

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'manual-queue';
            });

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);
    }

    public function test_it_queue_attribute_is_used_when_on_queue_is_not_called()
    {
        $notification = new #[Queue('attribute-queue')] class extends Notification implements ShouldQueue
        {
            use Queueable;

            public function via($notifiable): string
            {
                return 'mail';
            }
        };

        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn(null);

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'attribute-queue';
            });

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);
    }

    public function test_it_constructor_override_takes_precedence_over_queue_attribute()
    {
        $notification = new #[Queue('attribute-queue')] class extends Notification implements ShouldQueue
        {
            use Queueable;

            public function __construct()
            {
                $this->queue = 'constructor-override-queue';
            }

            public function via($notifiable): string
            {
                return 'mail';
            }
        };

        $notifiable = Mockery::mock(Notifiable::class);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('getContainer')->andReturn(app());
        $manager->expects('resolveConnectionFromQueueRoute')->andReturn(null);

        $events = Mockery::mock(EventDispatcher::class);
        $events->expects('listen');

        $bus = Mockery::mock(BusDispatcher::class);
        $bus->expects('dispatch')
            ->withArgs(function ($job) {
                return $job->queue === 'constructor-override-queue';
            });

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);
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

class DummyQueuedNotificationWithArrayVia extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->connection = 'redis';
        $this->queue = 'dummy';
    }

    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
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

class DummyNotificationWithViaConnections extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->connection = 'redis';
        $this->queue = 'dummy';
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function viaConnections()
    {
        return [
            'database' => 'sync',
        ];
    }
}

class DummyNotificationWithViaQueues extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->connection = 'redis';
        $this->queue = 'dummy';
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function viaQueues()
    {
        return [
            'mail' => 'admin_notifications',
        ];
    }
}

class DummyNotificationWithMiddleware extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return 'mail';
    }

    public function middleware()
    {
        return [
            new TestNotificationMiddleware,
        ];
    }
}

class DummyMultiChannelNotificationWithConditionalMiddleware extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return [
            'mail',
            'database',
            'broadcast',
        ];
    }

    public function middleware($notifiable, $channel)
    {
        return match ($channel) {
            'mail' => [new TestMailNotificationMiddleware],
            'database' => [new TestDatabaseNotificationMiddleware],
            default => []
        };
    }
}

class TestNotificationMiddleware
{
    public function handle($command, $next)
    {
        return $next($command);
    }
}

class TestMailNotificationMiddleware
{
    public function handle($command, $next)
    {
        return $next($command);
    }
}

class TestDatabaseNotificationMiddleware
{
    public function handle($command, $next)
    {
        return $next($command);
    }
}

class DummyNotificationWithViaMutation extends Notification
{
    public $channelData = null;

    public function via($notifiable)
    {
        $this->channelData = $notifiable->routeConfig ?? 'default';

        return 'mail';
    }
}
