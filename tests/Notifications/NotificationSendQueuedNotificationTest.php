<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class NotificationSendQueuedNotificationTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testNotificationsCanBeSent()
    {
        $job = new SendQueuedNotifications('notifiables', 'notification');
        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('sendNow')->once()->withArgs(function ($notifiables, $notification, $channels) {
            return $notifiables instanceof Collection && $notifiables->toArray() === ['notifiables']
                && $notification === 'notification'
                && $channels === null;
        });
        $job->handle($manager);
    }

    public function testSerializationOfNotifiableModel()
    {
        $identifier = new ModelIdentifier(NotifiableUser::class, [null], [], null);
        $serializedIdentifier = serialize($identifier);

        $job = new SendQueuedNotifications(new NotifiableUser, 'notification');
        $serialized = serialize($job);

        $this->assertStringContainsString($serializedIdentifier, $serialized);
    }

    public function testSerializationOfNormalNotifiable()
    {
        $notifiable = new AnonymousNotifiable;
        $serializedNotifiable = serialize($notifiable);

        $job = new SendQueuedNotifications($notifiable, 'notification');
        $serialized = serialize($job);

        $this->assertStringContainsString($serializedNotifiable, $serialized);
    }

    public function testDefaultDisplayName()
    {
        $notifiable = new AnonymousNotifiable;

        $job = new SendQueuedNotifications($notifiable, new NotificationWithDefaultName);

        $this->assertSame(NotificationWithDefaultName::class, $job->displayName());
    }

    public function testOverriddenDisplayName()
    {
        $notifiable = new AnonymousNotifiable;

        $job = new SendQueuedNotifications($notifiable, new NotificationWithOverriddenName('overridden-name'));

        $this->assertSame('overridden-name', $job->displayName());
    }
}

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}

class NotificationWithDefaultName extends Notification
{

}

class NotificationWithOverriddenName extends Notification
{
    /** @var string */
    private $displayName;

    public function __construct(string $displayName)
    {
        $this->displayName = $displayName;
    }

    /** @return string */
    public function displayName()
    {
        return $this->displayName;
    }
}
