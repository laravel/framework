<?php

namespace Illuminate\Notifications\Channels;

use Nexmo\Client as NexmoClient;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\NexmoMessage;

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
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Nexmo\Message\Message
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('nexmo', $notification)) {
            return;
        }

        $message = $notification->toNexmo($notifiable);

        if (is_string($message)) {
            $message = new NexmoMessage($message);
        }

        return $this->nexmo->message()->send([
            'type' => $message->type,
            'from' => $message->from ?: $this->from,
            'to' => $to,
            'text' => trim($message->content),
        ]);
    }
}
