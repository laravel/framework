<?php

namespace Illuminate\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\SlackAttachment;

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
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $url = $notifiable->routeNotificationFor('slack')) {
            return;
        }

        $message = $notification->toSlack($notifiable);

        $this->http->post($url, $this->buildJsonPayload($message));
    }

    /**
     * Build up a JSON payload for the Slack webhook.
     *
     * @param  \Illuminate\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function buildJsonPayload(SlackMessage $message)
    {
        $optionalFields = collect([
            'username' => null, 'icon_emoji' => null, 'channel' => null,
        ])->map(function ($value, $key) use ($message) {
            return $message->{$key};
        })->reject(function ($item) {
            return is_null($item);
        })->all();

        return [
            'json' => [
                'text' => $message->content,
                'attachments' => $this->attachments($message),
            ] + $optionalFields,
        ];
    }

    /**
     * Format the message's attachments.
     *
     * @param  \Illuminate\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function attachments(SlackMessage $message)
    {
        return collect($message->attachments)->map(function ($attachment) use ($message) {
            return array_filter([
                'color' => $message->color(),
                'title' => $attachment->title,
                'text' => $attachment->content,
                'title_link' => $attachment->url,
                'fields' => $this->fields($attachment),
            ]);
        })->all();
    }

    /**
     * Format the attachment's fields.
     *
     * @param  \Illuminate\Notifications\Messages\SlackAttachment  $attachment
     * @return array
     */
    protected function fields(SlackAttachment $attachment)
    {
        return collect($attachment->fields)->map(function ($value, $key) {
            return ['title' => $key, 'value' => $value, 'short' => true];
        })->values()->all();
    }
}
