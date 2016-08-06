<?php

namespace Illuminate\Notifications\Channels;

use Nexmo\Client as NexmoClient;
use Illuminate\Notifications\Notification;

class NexmoSmsChannel
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
     * Create a new Nexmo channel instance.
     *
     * @param  \Nexmo\Client  $nexmo
     * @param  string  $from
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
     * @param  \Illuminate\Support\Collection  $notifiables
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiables, Notification $notification)
    {
        foreach ($notifiables as $notifiable) {
            if (! $to = $notifiable->routeNotificationFor('nexmo')) {
                continue;
            }

            $this->nexmo->message()->send([
                'from' => $this->from,
                'to' => $to,
                'text' => $this->formatNotification($notifiable, $notification),
            ]);
        }
    }

    /**
     * Format the given notification to a single string.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    protected function formatNotification($notifiable, $notification)
    {
        $message = $notification->message($notifiable);

        $actionText = $message->actionText
                    ? $message->actionText.': ' : '';

        return trim(implode(PHP_EOL.PHP_EOL, array_filter([
            implode(' ', $message->introLines),
            $actionText.$message->actionUrl,
            implode(' ', $message->outroLines),
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
