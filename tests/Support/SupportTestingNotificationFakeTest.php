<?php

namespace Illuminate\Tests\Support;

use Exception;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class SupportTestingNotificationFakeTest extends TestCase
{
    /**
     * @var \Illuminate\Support\Testing\Fakes\NotificationFake
     */
    private $fake;

    /**
     * @var \Illuminate\Tests\Support\NotificationStub
     */
    private $notification;

    /**
     * @var \Illuminate\Tests\Support\UserStub
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new NotificationFake;
        $this->notification = new NotificationStub;
        $this->user = new UserStub;
    }

    public function testAssertSentTo()
    {
        try {
            $this->fake->assertSentTo($this->user, NotificationStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The expected [Illuminate\Tests\Support\NotificationStub] notification was not sent.'));
        }

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentTo($this->user, NotificationStub::class);
    }

    public function testAssertSentToClosure()
    {
        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentTo($this->user, function (NotificationStub $notification) {
            return true;
        });
    }

    public function testAssertSentOnDemand()
    {
        $this->fake->send(new AnonymousNotifiable, new NotificationStub);

        $this->fake->assertSentOnDemand(NotificationStub::class);
    }

    public function testAssertSentOnDemandClosure()
    {
        $this->fake->send(new AnonymousNotifiable, new NotificationStub);

        $this->fake->assertSentOnDemand(NotificationStub::class, function (NotificationStub $notification) {
            return true;
        });
    }

    public function testAssertNotSentTo()
    {
        $this->fake->assertNotSentTo($this->user, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        try {
            $this->fake->assertNotSentTo($this->user, NotificationStub::class);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\NotificationStub] notification was sent.'));
        }
    }

    public function testAssertNotSentToClosure()
    {
        $this->fake->send($this->user, new NotificationStub);

        try {
            $this->fake->assertNotSentTo($this->user, function (NotificationStub $notification) {
                return true;
            });
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('The unexpected [Illuminate\Tests\Support\NotificationStub] notification was sent.'));
        }
    }

    public function testAssertNothingSent()
    {
        $this->fake->assertNothingSent();
        $this->fake->send($this->user, new NotificationStub);

        try {
            $this->fake->assertNothingSent();
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Notifications were sent unexpectedly.'));
        }
    }

    public function testAssertNothingSentTo()
    {
        $this->fake->assertNothingSentTo($this->user);
        $this->fake->send($this->user, new NotificationStub);

        try {
            $this->fake->assertNothingSentTo($this->user);
            $this->fail();
        } catch (ExpectationFailedException $e) {
            $this->assertThat($e, new ExceptionMessage('Notifications were sent unexpectedly.'));
        }
    }

    public function testAssertSentToFailsForEmptyArray()
    {
        $this->expectException(Exception::class);

        $this->fake->assertSentTo([], NotificationStub::class);
    }

    public function testAssertSentToFailsForEmptyCollection()
    {
        $this->expectException(Exception::class);

        $this->fake->assertSentTo(new Collection, NotificationStub::class);
    }

    public function testResettingNotificationId()
    {
        $this->fake->send($this->user, $this->notification);

        $id = $this->notification->id;

        $this->fake->send($this->user, $this->notification);

        $this->assertSame($id, $this->notification->id);

        $this->notification->id = null;

        $this->fake->send($this->user, $this->notification);

        $this->assertNotNull($this->notification->id);
        $this->assertNotSame($id, $this->notification->id);
    }

    public function testAssertTimesSent()
    {
        $this->fake->assertTimesSent(0, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send(new UserStub, new NotificationStub);

        $this->fake->assertTimesSent(3, NotificationStub::class);
    }

    public function testAssertSentToTimes()
    {
        $this->fake->assertSentToTimes($this->user, NotificationStub::class, 0);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentToTimes($this->user, NotificationStub::class, 3);
    }

    public function testAssertSentOnDemandTimes()
    {
        $this->fake->assertSentOnDemandTimes(NotificationStub::class, 0);

        $this->fake->send(new AnonymousNotifiable, new NotificationStub);

        $this->fake->send(new AnonymousNotifiable, new NotificationStub);

        $this->fake->send(new AnonymousNotifiable, new NotificationStub);

        $this->fake->assertSentOnDemandTimes(NotificationStub::class, 3);
    }

    public function testAssertSentToWhenNotifiableHasPreferredLocale()
    {
        $user = new LocalizedUserStub;

        $this->fake->send($user, new NotificationStub);

        $this->fake->assertSentTo($user, NotificationStub::class, function ($notification, $channels, $notifiable, $locale) use ($user) {
            return $notifiable === $user && $locale === 'au';
        });
    }

    public function testAssertSentToWhenNotifiableHasFalsyShouldSend()
    {
        $user = new LocalizedUserStub;

        $this->fake->send($user, new NotificationWithFalsyShouldSendStub);

        $this->fake->assertNotSentTo($user, NotificationWithFalsyShouldSendStub::class);
    }
}

class NotificationStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}

class NotificationWithFalsyShouldSendStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function shouldSend($notifiable, $channel)
    {
        return false;
    }
}

class UserStub extends User
{
    //
}

class LocalizedUserStub extends User implements HasLocalePreference
{
    public function preferredLocale()
    {
        return 'au';
    }
}
