<?php

namespace Illuminate\Notifications\Transports;

class DatabaseTransport
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return void
     */
    public function send(Notification $notification)
    {
        foreach ($notification->notifiables as $notifiable) {
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
