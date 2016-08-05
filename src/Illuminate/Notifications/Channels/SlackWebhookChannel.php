<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Message;

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
     * @param  \Illuminate\Notifications\Message  $message
     * @return void
     */
    public function send($notifiables, Message $message)
    {
        foreach ($notifiables as $notifiable) {
            if (! $url = $notifiable->routeNotificationFor('slack')) {
                continue;
            }

            $this->http->post($url, [
                'json' => [
                    'attachments' => [
                        array_filter([
                            'color' => $this->color($message),
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
     * Format the given notification.
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
     * @param  \Illuminate\Notifications\Message  $message
     * @return string|null
     */
    protected function color(Message $message)
    {
        switch ($message->notification->level) {
            case 'success':
                return 'good';
            case 'error':
                return 'danger';
        }
    }
}
