<?php

namespace Illuminate\Tests\Notifications;

use JMac\Testing\Double;
use Exception;
use Illuminate\Bus\Dispatcher as BusDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationSkipped;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Testing\Fakes\BusFake;
use Illuminate\Support\Testing\Fakes\EventFake;
use Illuminate\Tests\Notifications\Fixtures\ChannelSpy;
use Laravel\SerializableClosure\SerializableClosure;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class NotificationChannelManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function testNotificationCanBeDispatchedToDriver()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $manager->expects('driver')->andReturn($driver);

        $manager->send($notifiable = new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);

        $this->assertCount(1, $driver->sent);
        $this->assertSame($notifiable, $driver->sent[0][0]);
        $this->assertInstanceOf(NotificationChannelManagerTestNotification::class, $driver->sent[0][1]);
        $events->assertDispatchedOnce(NotificationSending::class);
        $events->assertDispatchedOnce(NotificationSent::class);
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
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventsDispatcher;
        $container->instance(Dispatcher::class, $events);
        $calls = 0;
        $events->listen(NotificationSending::class, function () use (&$calls) {
            return $calls++ > 0;
        });
        $skipped = [];
        $events->listen(NotificationSkipped::class, function ($event) use (&$skipped) {
            $skipped[] = $event;
        });
        $sent = [];
        $events->listen(NotificationSent::class, function ($event) use (&$sent) {
            $sent[] = $event;
        });
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $manager->expects('driver')->andReturn($driver);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotificationWithTwoChannels);

        $this->assertCount(1, $driver->sent);
        $this->assertCount(1, $skipped);
        $this->assertCount(1, $sent);
    }

    public function testNotificationNotSentWhenCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $manager->shouldNotReceive('driver');

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestCancelledNotification);

        $events->assertDispatchedOnce(NotificationSkipped::class);
        $events->assertNotDispatched(NotificationSent::class);
    }

    public function testNotificationSentWhenNotCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $manager->expects('driver')->andReturn($driver);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotCancelledNotification);

        $this->assertCount(1, $driver->sent);
        $events->assertDispatchedOnce(NotificationSending::class);
        $events->assertDispatchedOnce(NotificationSent::class);
    }

    public function testNotificationNotSentWhenFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $driver->exception = new Exception();
        $manager->expects('driver')->andReturn($driver);

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);

        $events->assertDispatchedOnce(NotificationSending::class);
        $events->assertDispatchedOnce(NotificationFailed::class);
        $events->assertNotDispatched(NotificationSent::class);
    }

    public function testNotificationFailedDispatchedOnlyOnceWhenFailed()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventsDispatcher;
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new class($events)
        {
            public function __construct(private $events)
            {
            }

            public function send($notifiable, $notification)
            {
                $this->events->dispatch(new NotificationFailed($notifiable, $notification, 'test'));

                throw new Exception();
            }
        };
        $manager->expects('driver')->andReturn($driver);
        $failed = 0;
        $events->listen(NotificationFailed::class, function () use (&$failed) {
            $failed++;
        });
        $sent = 0;
        $events->listen(NotificationSent::class, function () use (&$sent) {
            $sent++;
        });

        try {
            $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
            $this->fail('Expected exception was not thrown.');
        } catch (Exception) {
            $this->assertSame(1, $failed);
            $this->assertSame(0, $sent);
        }
    }

    public function testNotificationFailedDispatchedOnlyOnceWhenMultipleFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventsDispatcher;
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = $container->make(ChannelManager::class, ['container' => $container]);
        $manager->extend('test', function () {
            return new class
            {
                private $count = 0;

                public function send($notifiable, Notification $notification)
                {
                    if ($this->count > 1) {
                        throw new \Exception();
                    }

                    $this->count++;
                }
            };
        });
        $failed = 0;
        $events->listen(NotificationFailed::class, function () use (&$failed) {
            $failed++;
        });
        $sent = 0;
        $events->listen(NotificationSent::class, function () use (&$sent) {
            $sent++;
        });

        try {
            $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
            $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
            $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
        } finally {
            $this->assertSame(2, $sent);
            $this->assertSame(1, $failed);
        }
    }

    public function testNotificationCanBeQueued()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance(QueueRoutes::class, $queueRoutes);
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);

        $bus->assertDispatched(SendQueuedNotifications::class);
    }

    public function testSendQueuedNotificationsCanBeOverrideViaContainer()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance(QueueRoutes::class, $queueRoutes);
        $container->instance('queue.routes', $queueRoutes);
        $container->bind(SendQueuedNotifications::class, TestSendQueuedNotifications::class);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);

        $bus->assertDispatched(TestSendQueuedNotifications::class);
    }

    public function testQueuedNotificationForwardsMessageGroupFromMethodToQueueJob()
    {
        $mockedMessageGroupId = 'group-1';

        $notification = $this->getMockBuilder(NotificationChannelManagerTestQueuedNotificationWithMessageGroupMethod::class)->onlyMethods(['messageGroup'])->getMock();
        $notification->expects($this->exactly(2))->method('messageGroup')->willReturn($mockedMessageGroupId);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance(QueueRoutes::class, $queueRoutes);
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(fn ($job) => $this->assertEquals($mockedMessageGroupId, $job->messageGroup));
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
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance(QueueRoutes::class, $queueRoutes);
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(fn ($job) => $this->assertEquals($mockedMessageGroupId, $job->messageGroup));
    }

    public function testQueuedNotificationForwardsMessageGroupSetToQueueJob()
    {
        $mockedMessageGroupSet = [
            'test' => 'group-1',
            'test2' => 'group-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance(QueueRoutes::class, $queueRoutes);
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithTwoChannels)->onGroup($mockedMessageGroupSet);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(fn ($job) => $this->assertEquals($mockedMessageGroupSet[$job->channels[0]], $job->messageGroup));
    }

    public function testQueuedNotificationForwardsMessageGroupSetFromClassToQueueJob()
    {
        $mockedMessageGroupSet = [
            'test' => 'group-1',
            'test2' => 'group-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithMessageGroups);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(fn ($job) => $this->assertEquals($mockedMessageGroupSet[$job->channels[0]], $job->messageGroup));
    }

    public function testQueuedNotificationForwardsDeduplicatorToQueueJob()
    {
        $mockedDeduplicator = fn ($payload, $queue) => 'deduplication-id-1';

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotification)->withDeduplicator($mockedDeduplicator);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $job = $bus->dispatched(SendQueuedNotifications::class)->sole();
        $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
        $this->assertEquals($mockedDeduplicator, $job->deduplicator->getClosure());
    }

    public function testQueuedNotificationForwardsDeduplicatorSetToQueueJob()
    {
        $mockedDeduplicatorSet = [
            'test' => fn ($payload, $queue) => 'deduplication-id-1',
            'test2' => fn ($payload, $queue) => 'deduplication-id-2',
        ];

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithTwoChannels)->withDeduplicator($mockedDeduplicatorSet);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(function ($job) use ($mockedDeduplicatorSet) {
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
            $this->assertEquals($mockedDeduplicatorSet[$job->channels[0]], $job->deduplicator->getClosure());
        });
    }

    public function testQueuedNotificationForwardsDeduplicatorSetFromClassToQueueJob()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithDeduplicators);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(fn ($job) => $this->assertEquals(
            $job->notification->deduplicatorResults[$job->channels[0]],
            call_user_func($job->deduplicator, '', null)
        ));
    }

    public function testQueuedNotificationForwardsDeduplicationIdMethodToQueueJob()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $queueRoutes = new QueueRoutes;
        $container->instance('queue.routes', $queueRoutes);
        Container::setInstance($container);
        $manager = new ChannelManager($container);

        $notification = (new NotificationChannelManagerTestQueuedNotificationWithDeduplicationId);
        $manager->send([new NotificationChannelManagerTestNotifiable], $notification);

        $jobs = $bus->dispatched(SendQueuedNotifications::class);
        $this->assertCount(2, $jobs);
        $jobs->each(function ($job) {
            $this->assertInstanceOf(SerializableClosure::class, $job->deduplicator);
            $this->assertSame(
                $job->notification->deduplicationId('payload', 'queue'),
                ($job->deduplicator->getClosure())('payload', 'queue')
            );
        });
    }

    public function testAfterSendingMethodAfterSendingNotification()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $bus = new BusFake(new BusDispatcher(new Container));
        $container->instance(Bus::class, $bus);
        $events = new EventFake(new EventsDispatcher);
        $container->instance(Dispatcher::class, $events);
        Container::setInstance($container);
        $manager = Double::for(ChannelManager::class)->passthru(new ChannelManager($container));
        $driver = new ChannelSpy;
        $manager->expects('driver')->andReturn($driver);
        $driver->response = $response = new stdClass;

        $manager->send($notifiable = new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerWithAfterSendingMethodNotification);

        $this->assertSame($notifiable, NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingNotifiable);
        $this->assertSame('test', NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingChannel);
        $this->assertSame($response, NotificationChannelManagerWithAfterSendingMethodNotification::$afterSendingResponse);

        $events->assertDispatchedOnce(NotificationSending::class);
        $events->assertDispatchedOnce(NotificationSent::class);
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
    public function send($notifiable, $notification)
    {
        //
    }
}
