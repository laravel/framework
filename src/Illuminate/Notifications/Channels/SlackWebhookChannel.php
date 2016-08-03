<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Notification;

class SlackWebhookChannel
{
    /**
     * The HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * Create a new Slack channel instance.
     *
     * @param  \GuzzleHttp\Client  $http
     * @return void
     */
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

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
            if (! $url = $notifiable->routeNotificationFor('slack')) {
                continue;
            }

            $this->http->post($url, [
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
     * @param  \Illuminate\Notifications\Notification  $notification
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
     * @param  \Illuminate\Notifications\Notification  $notification
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
