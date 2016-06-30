<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Notifications\Factory as NotificationFactory;

class NotificationRoutesNotificationsTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationCanBeDispatched()
    {
        $container = new Container;
        $factory = Mockery::mock(NotificationFactory::class);
        $container->instance(NotificationFactory::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new StdClass;
        $factory->shouldReceive('dispatch')->with($notifiable, $instance);
        Container::setInstance($container);

        $notifiable->notify($instance);
    }

    public function testNotificationCanBeDispatchedToGivenChannels()
    {
        $container = new Container;
        $factory = Mockery::mock(NotificationFactory::class);
        $container->instance(NotificationFactory::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new StdClass;
        $factory->shouldReceive('dispatch')->with($notifiable, $instance, ['channel']);
        Container::setInstance($container);

        $notifiable->notifyVia(['channel'], $instance);
    }

    public function testNotificationOptionRouting()
    {
        $instance = new RoutesNotificationsTestInstance;
        $this->assertEquals('bar', $instance->routeNotificationFor('foo'));
        $this->assertEquals('taylor@laravel.com', $instance->routeNotificationFor('mail'));
        $this->assertEquals('5555555555', $instance->routeNotificationFor('nexmo'));
    }
}

class RoutesNotificationsTestInstance
{
    use Illuminate\Notifications\RoutesNotifications;

    protected $email = 'taylor@laravel.com';
    protected $phone_number = '5555555555';

    public function routeNotificationForFoo()
    {
        return 'bar';
    }
}
