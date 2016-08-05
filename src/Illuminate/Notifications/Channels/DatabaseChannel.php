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
            $this->createNotification($notifiable, $notification);
        }
    }

    /**
     * Create a database notification record for the notifiable.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    protected function createNotification($notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database')->create([
            'type' => get_class($notification),
            'level' => $notification->level,
            'intro' => $notification->introLines,
            'outro' => $notification->outroLines,
            'action_text' => $notification->actionText,
            'action_url' => $notification->actionUrl,
            'read' => false,
        ]);
    }
}
