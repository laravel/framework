<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Testing\Fakes\NotificationFake;

class NotificationFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new NotificationFake;
        $this->notification = new NotificationStub;
        $this->user = new UserStub;
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\NotificationStub] notification was not sent.
     */
    public function testAssertSentTo()
    {
        $this->fake->assertSentTo($this->user, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentTo($this->user, NotificationStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The unexpected [Illuminate\Tests\Support\NotificationStub] notification was sent.
     */
    public function testAssertNotSentTo()
    {
        $this->fake->assertNotSentTo($this->user, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertNotSentTo($this->user, NotificationStub::class);
    }
}

class NotificationStub extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }
}

class UserStub extends User
{
}
