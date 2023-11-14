<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\PendingBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\NotificationSender;
use Illuminate\Notifications\SendQueuedNotifications;
use Mockery as m;
use PHPUnit\Framework\TestCase;

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

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithMiddleware);
    }

    public function testItCanSendQueuedMultiChannelNotificationsThroughDifferentMiddleware()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
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

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyMultiChannelNotificationWithConditionalMiddleware);
    }

    public function testItCanSendQueuedWithViaConnectionsNotifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
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

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->send($notifiable, new DummyNotificationWithViaConnections);
    }

    public function testItCanBatchQueuedNotificationsWithAStringVia()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $pendingBatch = m::mock(PendingBatch::class);
        $events = m::mock(EventDispatcher::class);
        $pendingBatch->shouldReceive('add')
            ->with(m::type(SendQueuedNotifications::class))
            ->once();

        $sender = new NotificationSender($manager, $bus, $events);

        $result = $sender->batch($notifiable, new DummyQueuedNotificationWithStringVia, $pendingBatch);

        $this->assertSame($pendingBatch, $result);
    }

    public function testItCanBatchQueuedNotificationsThroughMiddleware()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $pendingBatch = m::mock(PendingBatch::class);
        $pendingBatch->shouldReceive('add')
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestNotificationMiddleware;
            });
        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $result = $sender->batch($notifiable, new DummyNotificationWithMiddleware, $pendingBatch);

        $this->assertSame($pendingBatch, $result);
    }

    public function testItCanBatchQueuedMultiChannelNotificationsThroughDifferentMiddleware()
    {
        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $pendingBatch = m::mock(PendingBatch::class);
        $pendingBatch->shouldReceive('add')
            ->once()
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestMailNotificationMiddleware;
            });
        $pendingBatch->shouldReceive('add')
            ->once()
            ->withArgs(function ($job) {
                return $job->middleware[0] instanceof TestDatabaseNotificationMiddleware;
            });
        $pendingBatch->shouldReceive('add')
            ->once()
            ->withArgs(function ($job) {
                return empty($job->middleware);
            });
        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $result = $sender->batch($notifiable, new DummyMultiChannelNotificationWithConditionalMiddleware, $pendingBatch);

        $this->assertSame($pendingBatch, $result);
    }

    public function testItCanBatchQueuedWithViaConnectionsNotifications()
    {
        $notifiable = new AnonymousNotifiable;
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $pendingBatch = m::mock(PendingBatch::class);
        $pendingBatch->shouldReceive('add')
            ->once()
            ->withArgs(function ($job) {
                return $job->connection === 'sync' && $job->channels === ['database'];
            });
        $pendingBatch->shouldReceive('add')
            ->once()
            ->withArgs(function ($job) {
                return $job->connection === null && $job->channels === ['mail'];
            });

        $events = m::mock(EventDispatcher::class);

        $sender = new NotificationSender($manager, $bus, $events);

        $result = $sender->batch($notifiable, new DummyNotificationWithViaConnections, $pendingBatch);

        $this->assertSame($pendingBatch, $result);
    }

    public function testItCanOnlyBatchNotificationThatImplementShouldQueue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$notification must be queueable');

        $notifiable = m::mock(Notifiable::class);
        $manager = m::mock(ChannelManager::class);
        $bus = m::mock(BusDispatcher::class);
        $pendingBatch = m::mock(PendingBatch::class);
        $events = m::mock(EventDispatcher::class);
        $pendingBatch->shouldNotReceive('add')
            ->with(m::type(SendQueuedNotifications::class));

        $sender = new NotificationSender($manager, $bus, $events);

        $sender->batch($notifiable, new DummyNotificationWithEmptyStringVia, $pendingBatch);
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
