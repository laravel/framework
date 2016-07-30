<?php

use Illuminate\Container\Container;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Contracts\Bus\Dispatcher as Bus;

class NotificationChannelManagerTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationCanBeDispatchedToDriver()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance('events', $events = Mockery::mock());
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);
        $manager->shouldReceive('driver')->andReturn($driver = Mockery::mock());
        $driver->shouldReceive('send')->andReturnUsing(function ($notifiables, $notification) {
            $this->assertEquals('Name', $notification->application);
            $this->assertEquals('Logo', $notification->logoUrl);
            $this->assertEquals('test', $notification->introLines[0]);
            $this->assertEquals('Text', $notification->actionText);
            $this->assertEquals('url', $notification->actionUrl);
        });
        $events->shouldReceive('fire')->with(Mockery::type(Illuminate\Notifications\Events\NotificationSent::class));

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestNotification);
    }

    public function testNotificationCanBeQueued()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(Bus::class, $bus = Mockery::mock());
        $bus->shouldReceive('dispatch')->with(Mockery::type(Illuminate\Notifications\SendQueuedNotifications::class));
        Container::setInstance($container);
        $manager = Mockery::mock(ChannelManager::class.'[driver]', [$container]);

        $manager->send([new NotificationChannelManagerTestNotifiable], new NotificationChannelManagerTestQueuedNotification);
    }
}

class NotificationChannelManagerTestNotifiable
{
    use Illuminate\Notifications\Notifiable;
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

class NotificationChannelManagerTestQueuedNotification extends Notification implements Illuminate\Contracts\Queue\ShouldQueue
{
    use Illuminate\Bus\Queueable;

    public function via()
    {
        return ['test'];
    }

    public function message()
    {
        return $this->line('test')->action('Text', 'url');
    }
}
