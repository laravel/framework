<?php

namespace Illuminate\Notifications\Channels;

use Codebird;
use Illuminate\Notifications\Notification;

class TwitterChannel
{
    /**
     * The Twitter client instance.
     *
     * @var \Codebird
     */
    protected $twitter;

    /**
     * Create a new Twitter channel instance.
     *
     * @param  \Codebird  $twitter
     * @return void
     */
    public function __construct(Codebird $twitter)
    {
        $this->twitter = $twitter;
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return void
     */
    public function send($notifiables, Notification $notification)
    {
        foreach ($notifiables as $notifiable) {
            if (! $url = $notifiable->routeNotificationFor('twitter')) {
                continue;
            }

            $this->twitter->statuses_update([
                'status' => $this->format($notification),
            ]);
        }
    }

    /**
     * Format the given notification.
     *
     * @param  \Illuminate\Notifications\Channels\Notification  $notification
     * @return string
     */
    protected function format(Notification $notification)
    {
        $message = trim(implode(PHP_EOL.PHP_EOL, $notification->introLines));

        if ($notification->actionText) {
            $message .= ' <'.$notification->actionUrl.'|'.$notification->actionText.'> ';
        }

        $message .= trim(implode(PHP_EOL.PHP_EOL, $notification->outroLines));

        return trim($message);
    }
}
