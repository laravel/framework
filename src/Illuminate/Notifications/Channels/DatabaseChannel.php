<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Notifications\Message;

class DatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Message  $message
     * @return void
     */
    public function send($notifiables, Message $message)
    {
        foreach ($notifiables as $notifiable) {
            $this->createNotification($notifiable, $message);
        }
    }

    /**
     * Create a database notification record for the notifiable.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Message  $message
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    protected function createNotification($notifiable, Message $message)
    {
        return $notifiable->routeNotificationFor('database')->create([
            'type' => get_class($message->notification),
            'level' => $message->notification->level,
            'intro' => $message->introLines,
            'outro' => $message->outroLines,
            'action_text' => $message->actionText,
            'action_url' => $message->actionUrl,
            'read' => false,
        ]);
    }
}
