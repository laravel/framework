<?php

namespace Illuminate\Notifications\Channels;

use Illuminate\Container\Container;
use Nexmo\Client as NexmoClient;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\NexmoMessage;
use Nexmo\Client\Credentials\Basic as NexmoCredentials;
use Illuminate\Contracts\Notifications\Channels\Factory;
use Illuminate\Contracts\Notifications\Channels\Dispatcher;

class NexmoSmsChannel implements Factory, Dispatcher
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
        if (! $to = $notifiable->routeNotificationFor('nexmo')) {
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

    /**
     * Check for the driver capacity.
     *
     * @param  string  $driver
     * @return bool
     */
    public static function canHandleNotification($driver)
    {
        return in_array($driver, ['nexmo']);
    }

    /**
     * Create a new driver instance.
     *
     * @param  $driver
     * @return \Illuminate\Contracts\Notifications\Channels\Dispatcher
     */
    public static function createDriver($driver)
    {
        if(! static::canHandleNotification($driver)) return null;

        $app = Container::getInstance();

        return new static(
            new NexmoClient(new NexmoCredentials(
                $app['config']['services.nexmo.key'],
                $app['config']['services.nexmo.secret']
            )),
            $app['config']['services.nexmo.sms_from']
        );
    }
}
