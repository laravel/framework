<?php

namespace Illuminate\Notifications\Transports;

use GuzzleHttp\Client as HttpClient;

class SlackTransport
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
            $response = (new HttpClient)->post($notifiable->routeNotificationFor('slack'), [
                'json' => [
                    'attachments' => [
                        array_filter([
                            'color' => $this->color($notification),
                            'title' => $notification->subject,
                            'title_link' => $notification->actionUrl ?: null,
                            'text' => $this->format($notification),
                        ]),
                    ],
                ],
            ]);
        }
    }

    /**
     * Format the given notification.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return string
     */
    protected function format(Notification $notification)
    {
        $message = trim(implode(PHP_EOL.PHP_EOL, $notification->introLines));

        if ($notification->actionText) {
            $message .= PHP_EOL.PHP_EOL.'<'.$notification->actionUrl.'|'.$notification->actionText.'>';
        }

        $message .= PHP_EOL.PHP_EOL.trim(implode(PHP_EOL.PHP_EOL, $notification->outroLines));

        return trim($message);
    }

    /**
     * Get the color that should be applied to the notification.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return string|null
     */
    protected function color(Notification $notification)
    {
        switch ($notification->level) {
            case 'success':
                return 'good';
            case 'error':
                return 'danger';
        }
    }
}
