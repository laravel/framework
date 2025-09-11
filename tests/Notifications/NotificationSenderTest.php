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
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NotificationSenderTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testItCanSendQueuedNotificationsWithAStringVia()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('getContainer')->andReturn(app());
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldReceive('dispatch');
        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();

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
        $events->shouldReceive('listen')->once();

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithEmptyStringVia);
    }

    public function testItCannotSendNotificationsViaDatabaseForAnonymousNotifiables()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('getContainer')->andReturn(app());
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldNotReceive('dispatch');
        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithDatabaseVia);
    }

    public function testItCanSendQueuedNotificationsThroughMiddleware()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldReceive('dispatch')
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestNotificationMiddleware;
            });
        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();
        $manager->shouldReceive('getContainer')->andReturn(app());

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithMiddleware);
    }

    public function testItCanSendQueuedMultiChannelNotificationsThroughDifferentMiddleware()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('getContainer')->andReturn(app());
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestMailNotificationMiddleware;
            });
        $bus->shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestDatabaseNotificationMiddleware;
            });
        $bus->shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($job) {
                return empty($job->middleware);
            });
        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyMultiChannelNotificationWithConditionalMiddleware);
    }

    public function testItCanSendQueuedWithViaConnectionsNotifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('getContainer')->andReturn(app());
        $bus = m::mock(BusDispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($job) {
                return $job->connection === 'sync' && $job->channels === ['database'];
            });
        $bus->shouldReceive('dispatch')
            ->once()
            ->withArgs(function ($job) {
                return $job->connection === null && $job->channels === ['mail'];
            });

        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaConnections);
    }

    public function testNotificationFailedSentWithoutHttpTransportException()
    {
        $this->expectException(TransportException::class);

        $notifiable = new AnonymousNotifiable();
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('driver')->andReturn($driver = m::mock());
        $response = m::mock(ResponseInterface::class);
        $driver->shouldReceive('send')->andThrow(new HttpTransportException('Transport error', $response));
        $bus = m::mock(BusDispatcher::class);

        $events = m::mock(EventDispatcher::class);
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $events->shouldReceive('dispatch')->once()->withArgs(function ($event) {
            return $event instanceof NotificationFailed && $event->data['exception'] instanceof TransportException;
        });

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->sendNow($notifiable, new DummyNotificationWithViaConnections(), ['mail']);
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

class DummyNotificationWithViaConnections extends Notification implements ShouldQueue
{
    use Queueable;

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
