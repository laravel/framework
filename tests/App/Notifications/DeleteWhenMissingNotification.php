<?php

namespace Illuminate\Tests\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\SerializesModels;
use Illuminate\Tests\App\Models\DeleteNotificationModel;

#[DeleteWhenMissingModels]
class DeleteWhenMissingNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public static bool $sent = false;

    public function __construct(public DeleteNotificationModel $model)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        static::$sent = true;

        return new MailMessage;
    }
}
