<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Support\Collection;
use Illuminate\Tests\Notifications\Fixtures\Models\NotifiableUser;
use Mockery;
use PHPUnit\Framework\TestCase;

class NotificationSendQueuedNotificationTest extends TestCase
{
    public function testNotificationsCanBeSent()
    {
        $notification = new TestNotification;
        $job = new SendQueuedNotifications('notifiables', $notification);
        $manager = Mockery::mock(ChannelManager::class);
        $manager->expects('sendNow')->withArgs(function ($notifiables, $notification, $channels) {
            return $notifiables instanceof Collection && $notifiables->toArray() === ['notifiables']
                && $notification instanceof TestNotification
                && $channels === null;
        });
        $job->handle($manager);
    }

    public function testSerializationOfNotifiableModel()
    {
        $identifier = new ModelIdentifier(NotifiableUser::class, [null], [], null);
        $serializedIdentifier = serialize($identifier);

        $job = new SendQueuedNotifications(new NotifiableUser, new TestNotification);
        $serialized = serialize($job);

        $this->assertStringContainsString($serializedIdentifier, $serialized);
    }

    public function testSerializationOfNormalNotifiable()
    {
        $notifiable = new AnonymousNotifiable;
        $serializedNotifiable = serialize($notifiable);

        $job = new SendQueuedNotifications($notifiable, new TestNotification);
        $serialized = serialize($job);

        $this->assertStringContainsString($serializedNotifiable, $serialized);
    }

    public function testNotificationCanSetMaxExceptions()
    {
        $notifiable = new NotifiableUser;
        $notification = new class
        {
            public $maxExceptions = 23;
        };

        $job = new SendQueuedNotifications($notifiable, $notification);

        $this->assertEquals(23, $job->maxExceptions);
    }

    public function testNotificationRespectsFailOnTimeoutAttribute()
    {
        $job = new SendQueuedNotifications(new NotifiableUser, new FailOnTimeoutNotification);

        $this->assertTrue($job->failOnTimeout);
    }
}

class TestNotification extends Notification
{
}

#[FailOnTimeout]
class FailOnTimeoutNotification extends Notification
{
}
