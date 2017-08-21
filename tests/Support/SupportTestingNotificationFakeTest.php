<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Illuminate\Notifications\Notification;
use Illuminate\Foundation\Auth\User;

class NotificationFakeTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->fake = new NotificationFake;
        $this->notification = new NotificationStub;
        $this->userStub = new UserStub;
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The expected [Illuminate\Tests\Support\NotificationStub] notification was not sent.
     */
    public function testAssertSentTo()
    {
        $this->fake->assertSentTo($this->userStub, NotificationStub::class);

        $this->fake->send($this->userStub, new NotificationStub);

        $this->fake->assertSentTo($this->userStub, NotificationStub::class);
    }

    /**
     * @expectedException PHPUnit\Framework\ExpectationFailedException
     * @expectedExceptionMessage The unexpected [Illuminate\Tests\Support\NotificationStub] notification was sent.
     */
    public function testAssertNotSentTo()
    {
        $this->fake->assertNotSentTo($this->userStub, NotificationStub::class);

        $this->fake->send($this->userStub, new NotificationStub);

        $this->fake->assertNotSentTo($this->userStub, NotificationStub::class);
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
