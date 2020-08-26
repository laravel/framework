<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use PHPUnit\Framework\TestCase;

class NotificationHelpersTest extends TestCase
{
    public function testNotifySendNotificationToNotifiableEntity()
    {
        NotificationFacade::fake(TestMailNotificationForAnonymousNotifiable::class);

        $notifiableEntity = new AnonymousNotifiable();
        notify($notifiableEntity, new TestMailNotificationForAnonymousNotifiable());

        NotificationFacade::assertSentTo($notifiableEntity, TestMailNotificationForAnonymousNotifiable::class);
    }
}

class TestMailNotificationForAnonymousNotifiable extends Notification
{
    public function via($notifiable)
    {
        return [TestCustomChannel::class, AnotherTestCustomChannel::class];
    }
}

class TestCustomChannel
{
    public function send($notifiable, $notification)
    {
        $_SERVER['__notifiable.route'][] = $notifiable->routeNotificationFor('testchannel');
    }
}

class AnotherTestCustomChannel
{
    public function send($notifiable, $notification)
    {
        $_SERVER['__notifiable.route'][] = $notifiable->routeNotificationFor('anothertestchannel');
    }
}
