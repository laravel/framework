<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Mockery;

class TestMailNotificationWithMailable extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        $mailable = Mockery::mock(Mailable::class);

        $mailable->expects('send');

        return $mailable;
    }
}
