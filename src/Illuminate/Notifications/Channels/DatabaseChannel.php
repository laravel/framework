<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Contracts\Notifications\ProvidesPayload;

class DatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
     */
    public function send(Notification $notification)
    {
        foreach ($notification->notifiables as $notifiable) {
            $payload = ($notification instanceof ProvidesPayload) ? $notification->getPayload() : null;

            $notifiable->routeNotificationFor('database')->create([
                'level' => $notification->level,
                'intro' => $notification->introLines,
                'outro' => $notification->outroLines,
                'action_text' => $notification->actionText,
                'action_url' => $notification->actionUrl,
                'payload' => $payload,
                'read' => false,
            ]);
        }
    }
}
