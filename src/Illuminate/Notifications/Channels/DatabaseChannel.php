<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Notification;

class DatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiables, Notification $notification)
    {
        foreach ($notifiables as $notifiable) {
            $notifiable->routeNotificationFor('database')->create([
                'level' => $notification->level,
                'intro' => $notification->introLines,
                'outro' => $notification->outroLines,
                'action_text' => $notification->actionText,
                'action_url' => $notification->actionUrl,
                'read' => false,
            ]);
        }
    }
}
