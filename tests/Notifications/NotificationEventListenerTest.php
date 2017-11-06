<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Container\Container;
use Illuminate\Notifications\Messages\MailMessage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NotificationEventListenerTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testNotificationsDispatchesOnEvents()
    {
        $container = new Container;
        $container->instance('config', ['app.name' => 'Name', 'app.logo' => 'Logo']);
        $container->instance(\Illuminate\Contracts\Notifications\Dispatcher::class, $dispatcher = Mockery::mock());
        Container::setInstance($container);

        $dispatcher->shouldReceive('sendNow')->once()->with('notifiable', Mockery::type(NotificationEventListenerNotification::class));

        $eventDispatcher = new \Illuminate\Events\Dispatcher;
        $eventDispatcher->listen(NotificationEventListenerEvent::class, NotificationEventListenerNotification::class);
        $eventDispatcher->dispatch(new NotificationEventListenerEvent('notifiable', 'foo'));
    }
}

class NotificationEventListenerEvent
{
    public $user;

    public $order;

    public function __construct($user, $order)
    {
        $this->user = $user;
        $this->order = $order;
    }
}

class NotificationEventListenerNotification extends Notification
{
    public $order;

    public function via()
    {
        return [NotificationEventListenerChannel::class];
    }

    public function routeNotificationForEvent($event)
    {
        return $event->user;
    }

    public function __construct($order)
    {
        $this->order = $order;
    }
}
