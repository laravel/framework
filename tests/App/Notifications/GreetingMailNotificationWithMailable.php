<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Tests\App\Mail\GreetingMailable;

class GreetingMailNotificationWithMailable extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new GreetingMailable)
            ->to($notifiable->email);
    }
}
