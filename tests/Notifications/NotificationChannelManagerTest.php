<?php

namespace Illuminate\Tests\Notifications;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class NotificationChannelManagerTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationCanBeDispatchedToDriver()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = Mockery::mock());
        $container->instance(Dispatcher::class, $events = Mockery::mock());
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $manager->shouldReceive('driver')->andReturn($driver = Mockery::mock());
        $events->shouldReceive('until')->with(Mockery::type(\Illuminate\Notifications\Events\NotificationSending::class))->andReturn(true);
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->with(Mockery::type(\Illuminate\Notifications\Events\NotificationSent::class));

        $manager->send(new NotificationChannelManagerTestNotifiable, new NotificationChannelManagerTestNotification);
    }

    public function testNotificationNotSentOnHalt()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = Mockery::mock());
        $container->instance(Dispatcher::class, $events = Mockery::mock());
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $events->shouldReceive('until')->once()->with(Mockery::type(\Illuminate\Notifications\Events\NotificationSending::class))->andReturn(false);
        $events->shouldReceive('until')->with(Mockery::type(\Illuminate\Notifications\Events\NotificationSending::class))->andReturn(true);
        $manager->shouldReceive('driver')->once()->andReturn($driver = Mockery::mock());
        $driver->shouldReceive('send')->once();
        $events->shouldReceive('dispatch')->with(Mockery::type(\Illuminate\Notifications\Events\NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotificationWithTwoChannels);
    }

    public function testNotificationCanBeQueued()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Dispatcher::class, $events = Mockery::mock());
        $container->instance(Bus::class, $bus = Mockery::mock());
        $bus->shouldReceive('dispatch')->with(Mockery::type(\Illuminate\Notifications\SendQueuedNotifications::class));
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }
}

class NotificationChannelManagerTestNotifiable
{
    use \Illuminate\Notifications\Notifiable;
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

class NotificationChannelManagerTestQueuedNotification extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use \Illuminate\Bus\Queueable;

    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}
