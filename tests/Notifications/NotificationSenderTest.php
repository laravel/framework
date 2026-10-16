<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\NotificationSender;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Support\Testing\Fakes\BusFake;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Tests\Notifications\Fixtures\ChannelSpy;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NotificationSenderTest extends TestCase
{
    use VerifiesDoubles;

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    protected function getManager(array $queueRoutes = []): ChannelManager
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);

        if ($queueRoutes) {
            $routes = new QueueRoutes;

            foreach ($queueRoutes as $class => [$queue, $connection]) {
                $routes->set($class, $queue, $connection);
            }

            $container->instance('queue.routes', $routes);
        }

        Container::setInstance($container);

        return new ChannelManager($container);
    }

    public function test_it_can_send_queued_notifications_with_a_string_via()
    {
        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));
        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithStringVia);

        $bus->assertDispatched(SendQueuedNotifications::class);
    }

    public function test_it_can_send_queued_notifications_with_an_array_via()
    {
        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithArrayVia);

        $bus->assertDispatchedTimes(SendQueuedNotifications::class, 2);
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'dummy' && $job->channels === ['database'] && $job->connection === 'redis';
        });
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'dummy' && $job->channels === ['mail'] && $job->connection === 'redis';
        });
    }

    public function test_it_can_send_notifications_with_an_empty_string_via()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));
        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithEmptyStringVia);

        $bus->assertNothingDispatched();
    }

    public function test_it_cannot_send_notifications_via_database_for_anonymous_notifiables()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));
        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithDatabaseVia);

        $bus->assertNothingDispatched();
    }

    public function test_it_can_send_queued_notifications_through_middleware()
    {
        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));
        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithMiddleware);

        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return ($job->middleware[0] ?? null) instanceof TestNotificationMiddleware;
        });
    }

    public function test_it_can_send_queued_multi_channel_notifications_through_different_middleware()
    {
        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));
        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyMultiChannelNotificationWithConditionalMiddleware);

        $bus->assertDispatchedTimes(SendQueuedNotifications::class, 3);
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return ($job->middleware[0] ?? null) instanceof TestMailNotificationMiddleware;
        });
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return ($job->middleware[0] ?? null) instanceof TestDatabaseNotificationMiddleware;
        });
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return empty($job->middleware);
        });
    }

    public function test_it_can_send_queued_with_via_connections_notifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaConnections);

        $bus->assertDispatchedTimes(SendQueuedNotifications::class, 2);
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->connection === 'sync' && $job->channels === ['database'] && $job->queue === 'dummy';
        });
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->connection === 'redis' && $job->channels === ['mail'] && $job->queue === 'dummy';
        });
    }

    public function test_it_can_send_queued_with_via_queues_notifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = $this->getManager();
        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaQueues);

        $bus->assertDispatchedTimes(SendQueuedNotifications::class, 2);
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'dummy' && $job->channels === ['database'] && $job->connection === 'redis';
        });
        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'admin_notifications' && $job->channels === ['mail'] && $job->connection === 'redis';
        });
    }

    public function test_it_can_send_queued_notifications_with_queue_route()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = $this->getManager([
            DummyQueuedNotificationWithStringVia::class => ['notification-queue', 'notification-connection'],
        ]);

        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventDispatcher;

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyQueuedNotificationWithStringVia);

        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'notification-queue' && $job->channels === ['mail'] && $job->connection === 'notification-connection';
        });
    }

    public function test_notification_failed_sent_without_http_transport_exception()
    {
        $notifiable = new AnonymousNotifiable;
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $response = Double::for(ResponseInterface::class);
        $driver->exception = new HttpTransportException('Transport error', $response);
        $manager->expects('driver')->returns($driver);
        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventDispatcher;
        $failed = null;
        $events->listen(NotificationFailed::class, function ($event) use (&$failed) {
            $failed = $event;
        });

        $sender = new NotificationSender($manager, $bus, $events);

        try {
            $sender->sendNow($notifiable, new DummyNotificationWithViaConnections, ['mail']);
            $this->fail('Expected exception was not thrown.');
        } catch (TransportException) {
            $this->assertInstanceOf(TransportException::class, $failed->data['exception']);
        }
    }

    public function test_it_preserves_notification_state_mutated_in_via_method()
    {
        $notifiable = new AnonymousNotifiable;
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $manager->expects('driver')->returns($driver);
        $bus = new BusFake(new BusDispatcher(new Container));

        $events = new EventFake(new EventDispatcher);

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithViaMutation);

        $this->assertSame('default', $driver->sent[0][1]->channelData);
        $events->assertDispatched(NotificationSending::class);
        $events->assertDispatched(NotificationSent::class);
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

        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();

        $events = new EventDispatcher;

        $bus = new BusFake(new BusDispatcher(new Container));

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);

        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'manual-queue';
        });
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

        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();

        $events = new EventDispatcher;

        $bus = new BusFake(new BusDispatcher(new Container));

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);

        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'attribute-queue';
        });
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

        $notifiable = new DummyNotifiable;
        $manager = $this->getManager();

        $events = new EventDispatcher;

        $bus = new BusFake(new BusDispatcher(new Container));

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, $notification);

        $bus->assertDispatched(SendQueuedNotifications::class, function ($job) {
            return $job->queue === 'constructor-override-queue';
        });
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

class DummyNotifiable
{
    use Notifiable;
}
