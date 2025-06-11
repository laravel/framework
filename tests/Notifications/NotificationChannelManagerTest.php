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
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class NotificationChannelManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    public function testNotificationCanBeDispatchedToDriver()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock());
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $manager->shouldReceive('driver')->andReturn($driver = m::mock());
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->with(m::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationNotSentOnHalt()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock());
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->once()->with(m::type(NotificationSending::class))->andReturn(false);
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $manager->shouldReceive('driver')->once()->andReturn($driver = m::mock());
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->with(m::type(NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotificationWithTwoChannels);
    }

    public function testNotificationNotSentWhenCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock());
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $manager->shouldNotReceive('driver');
        $events->shouldNotReceive('dispatch');

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestCancelledNotification);
    }

    public function testNotificationSentWhenNotCancelled()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock());
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $manager->shouldReceive('driver')->once()->andReturn($driver = m::mock());
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->once()->with(m::type(NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotCancelledNotification);
    }

    public function testNotificationNotSentWhenFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock());
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $manager->shouldReceive('driver')->andReturn($driver = m::mock());
        $driver->shouldReceive('send')->andThrow(new Exception());
        $events->shouldReceive('listen')->once();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $events->shouldReceive('dispatch')->once()->with(m::type(NotificationFailed::class));
        $events->shouldReceive('dispatch')->never()->with(m::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationFailedDispatchedOnlyOnceWhenFailed()
    {
        $this->expectException(Exception::class);

        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = m::mock());
        $container->instance(Dispatcher::class, $events = m::mock(Dispatcher::class));
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $manager->shouldReceive('driver')->andReturn($driver = m::mock());
        $driver->shouldReceive('send')->andReturnUsing(function ($notifiable, $notification) use ($events) {
            $events->dispatch(new NotificationFailed($notifiable, $notification, 'test'));
            throw new Exception();
        });
        $listeners = new Collection();
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $events->shouldReceive('listen')->once()->andReturnUsing(function ($event, $callback) use ($listeners) {
            $listeners->push($callback);
        });
        $events->shouldReceive('dispatch')->once()->with(m::type(NotificationFailed::class))->andReturnUsing(function ($event) use ($listeners) {
            foreach ($listeners as $listener) {
                $listener($event);
            }
        });
        $events->shouldReceive('dispatch')->never()->with(m::type(NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationCanBeQueued()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Dispatcher::class, $events = m::mock());
        $container->instance(Bus::class, $bus = m::mock());
        $bus->shouldReceive('dispatch')->with(m::type(SendQueuedNotifications::class));
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('listen')->once();

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }

    public function testSendQueuedNotificationsCanBeOverrideViaContainer()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Dispatcher::class, $events = m::mock());
        $container->instance(Bus::class, $bus = m::mock());
        $bus->shouldReceive('dispatch')->with(m::type(TestSendQueuedNotifications::class));
        $container->bind(SendQueuedNotifications::class, TestSendQueuedNotifications::class);
        Container::setInstance($container);
        $manager = m::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('listen')->once();

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
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
