<?php

use Illuminate\Notifications\Notification;

class NotificationNotificationTest extends PHPUnit_Framework_TestCase
{
    public function testLevelCanBeRetrieved()
    {
        $notification = new Notification;
        $this->assertEquals('info', $notification->level);

        $notification = new NotificationTestNotification;
        $notification->level('error');
        $this->assertEquals('error', $notification->level);
    }

    public function testChannelNotificationFormatsMultiLineText()
    {
        $notification = new Notification([]);
        $notification->with('
            This is a
            single line of text.
        ');

        $this->assertEquals('This is a single line of text.', $notification->introLines[0]);

        $notification = new Notification([]);
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
