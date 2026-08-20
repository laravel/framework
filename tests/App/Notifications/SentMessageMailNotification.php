<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SentMessageMailNotification extends Notification
{
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Example notification with attachment.')
            ->attach(__DIR__.'/../../Integration/Mail/Fixtures/blank_document.pdf', [
                'as' => 'blank_document.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
