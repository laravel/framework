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

    public function testAssertSentTo()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The expected [Illuminate\\Tests\\Support\\NotificationStub] notification was not sent.');

        $this->fake->assertSentTo($this->user, NotificationStub::class);

        $this->fake->send($this->user, new NotificationStub);

        $this->fake->assertSentTo($this->user, NotificationStub::class);
    }

    public function testAssertNotSentTo()
    {
        $this->expectException(\PHPUnit\Framework\ExpectationFailedException::class);
        $this->expectExceptionMessage('The unexpected [Illuminate\\Tests\\Support\\NotificationStub] notification was sent.');

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
