<?php

namespace Illuminate\Tests\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Carbon;
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

    public function testRatelimitsWillBeRespected()
    {
        Container::getInstance()->instance(RateLimiter::class, $limiter = new RateLimiter(new Repository(new ArrayStore)));
        $limiter->for('test', fn () => Limit::perSecond(1));

        $notifiable = new NotifiableUser;
        $notificationObject = new class implements ShouldQueue
        {
            use Queueable, InteractsWithQueue;

            public function middleware()
            {
                return [new RateLimited('test')];
            }
        };

        Carbon::setTestNow('2021-01-01 00:00:00');

        $manager = m::mock(ChannelManager::class);
        $manager->shouldReceive('sendNow')->once();

        $job = new SendQueuedNotifications($notifiable, $notificationObject);
        $job->handle($manager);
        $job->handle($manager);

        Carbon::setTestNow('2021-01-01 00:00:01');

        $manager->shouldReceive('sendNow')->once();
        $job->handle($manager);
    }
}

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}
