<?php

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\Notification as ChannelNotification;

class NotificationNotificationTest extends PHPUnit_Framework_TestCase
{
    public function testLevelCanBeRetrieved()
    {
        $notification = new Notification;
        $this->assertEquals('info', $notification->level());

        $notification = new NotificationTestNotification;
        $this->assertEquals('error', $notification->level());
    }

    public function testSubjectCanBeRetrieved()
    {
        $notification = new NotificationTestNotification;
        $this->assertEquals('Notification Test Notification', $notification->subject());

        $notification = new NotificationTestNotificationWithSubject;
        $this->assertEquals('Zonda', $notification->subject());
    }

    public function testMessageBuilderCanBeRetrieved()
    {
        $notification = new Notification;
        $this->assertInstanceOf('Illuminate\Notifications\MessageBuilder', $notification->line('Something'));
    }

    public function testChannelNotificationFormatsMultiLineText()
    {
        $notification = new ChannelNotification([]);
        $notification->with('
            This is a
            single line of text.
        ');

        $this->assertEquals('This is a single line of text.', $notification->introLines[0]);

        $notification = new ChannelNotification([]);
        $notification->with([
            'This is a',
            'single line of text.',
        ]);

        $this->assertEquals('This is a single line of text.', $notification->introLines[0]);
    }
}


class NotificationTestNotification extends Notification
{
    public $level = 'error';
}

class NotificationTestNotificationWithSubject extends Notification
{
    public $subject = 'Zonda';
}
