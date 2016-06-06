<?php

namespace Illuminate\Notifications\Transports;

use Illuminate\Support\Arr;
use Nexmo\Client as NexmoClient;

class NexmoSmsTransport
{
    /**
     * The Nexmo client instance.
     *
     * @var \Nexmo\Client
     */
    protected $nexmo;

    /**
     * The phone number notifications should be sent from.
     *
     * @var string
     */
    protected $from;

    /**
     * Create a new transport instance.
     *
     * @param  \Nexmo\Client  $nexmo
     * @return void
     */
    public function __construct(NexmoClient $nexmo, $from)
    {
        $this->from = $from;
        $this->nexmo = $nexmo;
    }

    /**
     * Send the given notification.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return void
     */
    public function send(Notification $notification)
    {
        foreach ($notification->notifiables as $notifiable) {
            $this->nexmo->message()->send([
                'from' => $this->from,
                'to' => $notifiable->routeNotificationFor('nexmo'),
                'text' => $this->formatNotification($notification),
            ]);
        }
    }

    /**
     * Format the given notification to a single string.
     *
     * @param  \Illuminate\Notifications\Transports\Notification  $notification
     * @return string
     */
    protected function formatNotification(Notification $notification)
    {
        $data = $notification->toArray();

        $actionText = $notification->actionText
                    ? $notification->actionText.': ' : '';

        return trim(implode(PHP_EOL.PHP_EOL, array_filter([
            implode(' ', Arr::get($data, 'introLines', [])),
            $actionText.Arr::get($data, 'actionUrl'),
            implode(' ', Arr::get($data, 'outroLines', [])),
        ])));
    }

    /**
     * Set the phone number that should be used to send notification.
     *
     * @param  string  $from
     * @return $this
     */
    public function sendNotificationsFrom($from)
    {
        $this->from = $from;

        return $this;
    }
}
