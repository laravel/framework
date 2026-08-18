<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Attributes\AfterCommit;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class AfterCommitAttributeTest extends TestCase
{
    public function testMailableWithoutTheAttributeDefersToTheQueueConnection()
    {
        $job = new SendQueuedMailable(new AfterCommitAttributeTestMailable);

        $this->assertNull($job->afterCommit);
    }

    public function testMailableWithTheAttributeIsSentAfterCommit()
    {
        $job = new SendQueuedMailable(new AfterCommitAttributeTestMailableWithAttribute);

        $this->assertTrue($job->afterCommit);
    }

    public function testNotificationWithoutTheAttributeDefersToTheQueueConnection()
    {
        $job = new SendQueuedNotifications(new Collection, new AfterCommitAttributeTestNotification);

        $this->assertNull($job->afterCommit);
    }

    public function testNotificationWithTheAttributeIsSentAfterCommit()
    {
        $job = new SendQueuedNotifications(new Collection, new AfterCommitAttributeTestNotificationWithAttribute);

        $this->assertTrue($job->afterCommit);
    }
}

class AfterCommitAttributeTestMailable extends Mailable
{
    //
}

#[AfterCommit]
class AfterCommitAttributeTestMailableWithAttribute extends Mailable
{
    //
}

class AfterCommitAttributeTestNotification extends Notification
{
    //
}

#[AfterCommit]
class AfterCommitAttributeTestNotificationWithAttribute extends Notification
{
    //
}
