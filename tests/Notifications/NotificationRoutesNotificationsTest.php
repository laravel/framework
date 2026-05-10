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

    public function testOnDemandNotificationsCannotUseDatabaseChannel()
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('The database channel does not support on-demand notifications.')
        );

        Notification::route('database', 'foo');
    }

    public function testOnDemandNotificationsCanUseEnumChannels()
    {
        $notifiable = Notification::route(NotificationRouteChannel::Mail, 'taylor@laravel.com')
            ->route(UnitEnumNotificationRouteChannel::Slack, '#general');

        $this->assertSame('taylor@laravel.com', $notifiable->routeNotificationFor('mail'));
        $this->assertSame('taylor@laravel.com', $notifiable->routeNotificationFor(NotificationRouteChannel::Mail));
        $this->assertSame('#general', $notifiable->routeNotificationFor('Slack'));
        $this->assertSame('#general', $notifiable->routeNotificationFor(UnitEnumNotificationRouteChannel::Slack));
    }

    public function testOnDemandNotificationsCannotUseDatabaseEnumChannel()
    {
        $this->expectExceptionObject(
            new InvalidArgumentException('The database channel does not support on-demand notifications.')
        );

        Notification::route(NotificationRouteChannel::Database, 'foo');
    }
}

enum NotificationRouteChannel: string
{
    case Mail = 'mail';
    case Database = 'database';
}

enum UnitEnumNotificationRouteChannel
{
    case Slack;
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
