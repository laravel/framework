<?php

use Illuminate\Notifications\Message;
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
        $message = new Message(null, new Notification([]));
        $message->with('
            This is a
            single line of text.
        ');

        $this->assertEquals('This is a single line of text.', $message->introLines[0]);

        $message = new Message(null, new Notification([]));
        $message->with([
            'This is a',
            'single line of text.',
        ]);

        $this->assertEquals('This is a single line of text.', $message->introLines[0]);
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
