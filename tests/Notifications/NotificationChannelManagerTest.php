<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
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
        $events->shouldReceive('until')->with(m::type(NotificationSending::class))->andReturn(true);
        $manager->shouldReceive('driver')->once()->andReturn($driver = m::mock());
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->once()->with(m::type(NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotCancelledNotification);
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

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }
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
