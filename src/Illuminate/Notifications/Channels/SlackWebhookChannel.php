<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Message;
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

            $message = $notification->message($notifiable);

            $this->http->post($url, [
                'json' => [
                    'attachments' => [
                        array_filter([
                            'color' => $this->color($message->level),
                            'title' => $message->subject,
                            'title_link' => $message->actionUrl ?: null,
                            'text' => $this->format($message),
                        ]),
                    ],
                ],
            ]);
        }
    }

    /**
     * Format the given notification message.
     *
     * @param  \Illuminate\Notifications\Message  $message
     * @return string
     */
    protected function format(Message $message)
    {
        $text = trim(implode(PHP_EOL.PHP_EOL, $message->introLines));

        if ($message->actionText) {
            $text .= PHP_EOL.PHP_EOL.'<'.$message->actionUrl.'|'.$message->actionText.'>';
        }

        $text .= PHP_EOL.PHP_EOL.trim(implode(PHP_EOL.PHP_EOL, $message->outroLines));

        return trim($text);
    }

    /**
     * Get the color that should be applied to the notification.
     *
     * @param  string  $level
     * @return string|null
     */
    protected function color($level)
    {
        switch ($level) {
            case 'success':
                return 'good';
            case 'error':
                return 'danger';
        }
    }
}
