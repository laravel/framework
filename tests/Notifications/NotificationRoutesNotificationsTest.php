<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Container\Container;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Notifications\RoutesNotifications;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class NotificationRoutesNotificationsTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testNotificationCanBeDispatched()
    {
        $container = new Container;
        $factory = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new stdClass;
        $factory->shouldReceive('send')->with($notifiable, $instance);
        Container::setInstance($container);

        $notifiable->notify($instance);
    }

    public function testNotificationCanBeSentNow()
    {
        $container = new Container;
        $factory = m::mock(Dispatcher::class);
        $container->instance(Dispatcher::class, $factory);
        $notifiable = new RoutesNotificationsTestInstance;
        $instance = new stdClass;
        $factory->shouldReceive('sendNow')->with($notifiable, $instance, null);
        Container::setInstance($container);

        $notifiable->notifyNow($instance);
    }

    public function testNotificationOptionRouting()
    {
        $instance = new RoutesNotificationsTestInstance;
        $this->assertSame('bar', $instance->routeNotificationFor('foo'));
        $this->assertSame('taylor@laravel.com', $instance->routeNotificationFor('mail'));
    }

    public function testNotificationOptionRoutingWithUnitEnum()
    {
        $instance = new RoutesNotificationsTestInstance;
        $this->assertSame('bar', $instance->routeNotificationFor(RoutesNotificationsChannel::Foo));
        $this->assertSame('taylor@laravel.com', $instance->routeNotificationFor(RoutesNotificationsChannel::Mail));
    }

    public function testOnDemandNotificationsCanRouteWithUnitEnum()
    {
        $notifiable = Notification::route(RoutesNotificationsChannel::Foo, 'bar');

        $this->assertSame('bar', $notifiable->routeNotificationFor('foo'));
        $this->assertSame('bar', $notifiable->routeNotificationFor(RoutesNotificationsChannel::Foo));
    }

    public function testOnDemandNotificationsCannotUseDatabaseChannel()
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('The database channel does not support on-demand notifications.')
        );

        Notification::route('database', 'foo');
    }

    public function testOnDemandNotificationsCannotUseDatabaseChannelWithUnitEnum()
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('The database channel does not support on-demand notifications.')
        );

        Notification::route(RoutesNotificationsChannel::Database, 'foo');
    }
}

class RoutesNotificationsTestInstance
{
    use RoutesNotifications;

    protected $email = 'taylor@laravel.com';

    public function routeNotificationForFoo()
    {
        return 'bar';
    }
}

enum RoutesNotificationsChannel: string
{
    case Foo = 'foo';
    case Mail = 'mail';
    case Database = 'database';
}
