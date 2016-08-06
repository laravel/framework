<?php

namespace Illuminate\Notifications;

use BadMethodCallException;
use Illuminate\Queue\SerializesModels;

class Notification
{
    use SerializesModels;

    /**
     * The name of the application sending the notification.
     *
     * @var string
     */
    public $application;

    /**
     * The URL to the application's logo.
     *
     * @var string
     */
    public $logoUrl;

    /**
     * Specify the name of the application sending the notification.
     *
     * @param  string  $application
     * @param  string  $logoUrl
     * @return $this
     */
    public function application($application, $logoUrl = null)
    {
        $this->application = $application;
        $this->logoUrl = $logoUrl;

        return $this;
    }

    /**
     * Get an array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $message = $this->message($notifiable);

        return [
            'application' => $this->application,
            'logoUrl' => $this->logoUrl,
            'level' => $message->level,
            'subject' => $message->subject,
            'introLines' => $message->introLines,
            'outroLines' => $message->outroLines,
            'actionText' => $message->actionText,
            'actionUrl' => $message->actionUrl,
        ];
    }

    /**
     * Dynamically pass calls to the message class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists(Message::class, $method)) {
            return (new Message)->{$method}(...$parameters);
        }

        throw new BadMethodCallException("Call to undefined method [{$method}].");
    }
}
