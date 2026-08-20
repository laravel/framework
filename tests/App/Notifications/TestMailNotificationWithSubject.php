<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestMailNotificationWithSubject extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('mail custom subject')
            ->line('The introduction to the notification.');
    }
}
