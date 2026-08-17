<?php

namespace Illuminate\Tests\Notifications;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery;
use PHPUnit\Framework\TestCase;

class NotificationChannelManagerTest extends TestCase
{
    public function testNotificationCanBeDispatchedToDriver()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $driver->expects('send');
        $events->expects('dispatch')->with(Mockery::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testChannelCanBeResolvedUsingBackedEnum()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);

        $manager = new ChannelManager($container);
        $manager->extend('test', fn () => new NotificationChannelManagerTestCustomChannel);

        $this->assertInstanceOf(NotificationChannelManagerTestCustomChannel::class, $manager->channel(NotificationChannelManagerTestChannelEnum::Test));
    }

    public function testDriverCanBeResolvedUsingBackedEnum()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);

        $manager = new ChannelManager($container);

        $this->assertInstanceOf(NotificationChannelManagerTestCustomChannel::class, $manager->driver(NotificationChannelManagerTestChannelEnum::Custom));
    }

    public function testNotificationNotSentOnHalt()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(false);
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $driver->expects('send');
        $events->expects('dispatch')->with(Mockery::type(NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotificationWithTwoChannels);
    }

    public function testNotificationNotSentWhenCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');
        $manager->shouldNotReceive('driver');
        $events->shouldNotReceive('dispatch');

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestCancelledNotification);
    }

    public function testNotificationSentWhenNotCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $driver->expects('send');
        $events->expects('dispatch')->with(Mockery::type(NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotCancelledNotification);
    }

    public function testNotificationNotSentWhenFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $driver->expects('send')->andThrow(new Exception());
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $events->expects('dispatch')->with(Mockery::type(NotificationFailed::class));
        $events->shouldReceive('dispatch')->never()->with(Mockery::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationFailedDispatchedOnlyOnceWhenFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $driver->expects('send')->andReturnUsing(function ($notifiable, $notification) use ($events) {
            $events->dispatch(new NotificationFailed($notifiable, $notification, 'test'));
            throw new Exception();
        });
        $listeners = new Collection();
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $events->expects('listen')->andReturnUsing(function ($event, $callback) use ($listeners) {
            $listeners->push($callback);
        });
        $events->expects('dispatch')->with(Mockery::type(NotificationFailed::class))->andReturnUsing(function ($event) use ($listeners) {
            foreach ($listeners as $listener) {
                $listener($event);
            }
        });
        $events->shouldReceive('dispatch')->never()->with(Mockery::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationFailedDispatchedOnlyOnceWhenMultipleFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = $container->make(ChannelManager::class, ['container' => $container]);
        $manager->extend('test', function () use ($events) {
            return new class($events)
            {
                private $count = 0;

                public function __construct(private $events)
                {
                }

                public function send($notifiable, Notification $notification)
                {
                    if ($this->count > 1) {
                        throw new \Exception();
                    }

                    $this->count++;
                }
            };
        });
        $listeners = new Collection();
        $events->expects('until')->times(3)->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $events->expects('listen')->andReturnUsing(function ($event, $callback) use ($listeners) {
            $listeners->push($callback);
        });
        $events->expects('dispatch')->with(Mockery::type(NotificationFailed::class))->andReturnUsing(function ($event) use ($listeners) {
            foreach ($listeners as $listener) {
                $listener($event);
            }
        });
        $events->expects('dispatch')->times(2)->with(Mockery::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationCanBeQueued()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance(QueueRoutes::class, $queueRoutes);
        $queueRoutes->expects('getQueue')->andReturn(null);
        $queueRoutes->expects('getConnection')->andReturn(null);
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->with(Mockery::type(SendQueuedNotifications::class));
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }

    public function testSendQueuedNotificationsCanBeOverrideViaContainer()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance(QueueRoutes::class, $queueRoutes);
        $queueRoutes->expects('getQueue')->andReturn(null);
        $queueRoutes->expects('getConnection')->andReturn(null);
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->with(Mockery::type(TestSendQueuedNotifications::class));
        $container->bind(SendQueuedNotifications::class, TestSendQueuedNotifications::class);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }

    public function testQueuedNotificationForwardsMessageGroupFromMethodToQueueJob()
    {
        $mockedMessageGroupId = 'group-1';

        $notification = $this->getMockBuilder(NotificationChannelManagerTestQueuedNotificationWithMessageGroupMethod::class)->onlyMethods(['messageGroup'])->getMock();
        $notification->expects($this->exactly(2))->method('messageGroup')->willReturn($mockedMessageGroupId);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance(QueueRoutes::class, $queueRoutes);
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) use ($mockedMessageGroupId) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertEquals($mockedMessageGroupId, $job->messageGroup);

            return true;
        });
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsMessageGroupFromPropertyOverridingMethodToQueueJob()
    {
        $mockedMessageGroupId = 'group-1';

        // Ensure the messageGroup method is not called when a messageGroup property is provided.
        $notification = $this->getMockBuilder(NotificationChannelManagerTestQueuedNotificationWithMessageGroupMethod::class)->onlyMethods(['messageGroup'])->getMock();
        $notification->expects($this->never())->method('messageGroup')->willReturn('this-should-not-be-used');
        $notification->onGroup($mockedMessageGroupId);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance(QueueRoutes::class, $queueRoutes);
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) use ($mockedMessageGroupId) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertEquals($mockedMessageGroupId, $job->messageGroup);

            return true;
        });
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsMessageGroupSetToQueueJob()
    {
        $mockedMessageGroupSet = [
            'test' => 'group-1',
            'test2' => 'group-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance(QueueRoutes::class, $queueRoutes);
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) use ($mockedMessageGroupSet) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertEquals($mockedMessageGroupSet[$job->channels[0]], $job->messageGroup);

            return true;
        });
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithTwoChannels)->onGroup($mockedMessageGroupSet);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsMessageGroupSetFromClassToQueueJob()
    {
        $mockedMessageGroupSet = [
            'test' => 'group-1',
            'test2' => 'group-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) use ($mockedMessageGroupSet) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertEquals($mockedMessageGroupSet[$job->channels[0]], $job->messageGroup);

            return true;
        });
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithMessageGroups);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsDeduplicatorToQueueJob()
    {
        $mockedDeduplicator = fn ($payload, $queue) => 'deduplication-id-1';

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->withArgs(function ($job) use ($mockedDeduplicator) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
            $this->assertEquals($mockedDeduplicator, $job->deduplicator->getClosure());

            return true;
        });
        $queueRoutes->expects('getQueue')->andReturn(null);
        $queueRoutes->expects('getConnection')->andReturn(null);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotification)->withDeduplicator($mockedDeduplicator);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsDeduplicatorSetToQueueJob()
    {
        $mockedDeduplicatorSet = [
            'test' => fn ($payload, $queue) => 'deduplication-id-1',
            'test2' => fn ($payload, $queue) => 'deduplication-id-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) use ($mockedDeduplicatorSet) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
            $this->assertEquals($mockedDeduplicatorSet[$job->channels[0]], $job->deduplicator->getClosure());

            return true;
        });
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithTwoChannels)->withDeduplicator($mockedDeduplicatorSet);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsDeduplicatorSetFromClassToQueueJob()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertEquals($job->notification->deduplicatorResults[$job->channels[0]], call_user_func($job->deduplicator, '', null));

            return true;
        });
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithDeduplicators);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testQueuedNotificationForwardsDeduplicationIdMethodToQueueJob()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $queueRoutes = Mockery::mock();
        $container->instance('queue.routes', $queueRoutes);
        $bus->expects('dispatch')->times(2)->withArgs(function ($job) {
            $this->assertInstanceOf(SendQueuedNotifications::class, $job);
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
            $this->assertEquals($job->notification->deduplicationId(...), $job->deduplicator->getClosure());

            return true;
        });
        $queueRoutes->expects('getQueue')->times(2)->andReturn(null);
        $queueRoutes->expects('getConnection')->times(2)->andReturn(null);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->expects('listen');

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithDeduplicationId);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);
    }

    public function testAfterSendingMethodAfterSendingNotification()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = Mockery::mock();
        $container->instance(Bus::class, $bus);
        $events = Mockery::mock();
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $driver = Mockery::mock();
        $manager->expects('driver')->andReturn($driver);
        $events->expects('listen');
        $events->expects('until')->with(Mockery::type(NotificationSending::class))->andReturn(true);
        $response = Mockery::mock();
        $driver->expects('send')->andReturn($response);
        $events->expects('dispatch')->with(Mockery::type(NotificationSent::class));

        $manager->send($notifiable = new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerWithAfterSendingMethodNotification);

        $this->assertSame($notifiable, NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingNotifiable);
        $this->assertSame('test', NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingChannel);
        $this->assertSame($response, NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingResponse);
    }
}

class TestSendQueuedNotifications implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
}

class NotificationChannelManagerTestNotifiable
{
    use Notifiable;
}

class NotificationChannelManagerTestNotification extends Notification
{
    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}

class NotificationChannelManagerTestNotificationWithTwoChannels extends Notification
{
    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}

class NotificationChannelManagerTestCancelledNotification extends Notification
{
    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function shouldSend($notifiable, $channel)
    {
        return false;
    }
}

class NotificationChannelManagerTestNotCancelledNotification extends Notification
{
    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function shouldSend($notifiable, $channel)
    {
        return true;
    }
}

class NotificationChannelManagerTestQueuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}

class NotificationChannelManagerTestQueuedNotificationWithTwoChannels extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}

class NotificationChannelManagerTestQueuedNotificationWithMessageGroupMethod extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function messageGroup()
    {
        return 'group-1';
    }
}

class NotificationChannelManagerTestQueuedNotificationWithMessageGroups extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function withMessageGroups($notifiable, $channel)
    {
        return match ($channel) {
            'test' => 'group-1',
            'test2' => 'group-2',
            default => null,
        };
    }
}

class NotificationChannelManagerTestQueuedNotificationWithDeduplicators extends Notification implements ShouldQueue
{
    use Queueable;

    public $deduplicatorResults = [
        'test' => 'deduplication-id-1',
        'test2' => 'deduplication-id-2',
    ];

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function withDeduplicators($notifiable, $channel)
    {
        return match ($channel) {
            'test' => fn ($payload, $queue) => $this->deduplicatorResults['test'],
            'test2' => fn ($payload, $queue) => $this->deduplicatorResults['test2'],
            default => null,
        };
    }
}

class NotificationChannelManagerTestQueuedNotificationWithDeduplicationId extends Notification implements ShouldQueue
{
    use Queueable;

    public function via()
    {
        return ['test', 'test2'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }

    public function deduplicationId($payload, $queue)
    {
        return 'deduplication-id-1';
    }
}

class NotificationChannelManagerWithAfterSendingMethodNotification extends Notification
{
    public static $afterSendingNotifiable;
    public static $afterSendingChannel;
    public static $afterSendingResponse;

    public function via()
    {
        return ['test'];
    }

    public function afterSending($notifiable, $channel, $response)
    {
        static::$afterSendingNotifiable = $notifiable;
        static::$afterSendingChannel = $channel;
        static::$afterSendingResponse = $response;
    }
}

enum NotificationChannelManagerTestChannelEnum: string
{
    case Test = 'test';
    case Custom = NotificationChannelManagerTestCustomChannel::class;
}

class NotificationChannelManagerTestCustomChannel
{
}
