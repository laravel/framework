<?php

namespace Illuminate\Notifications;

use Illuminate\Queue\SerializesModels;

use function Illuminate\Support\enum_value;

class Notification
{
    use SerializesModels;

    /**
     * The unique identifier for the notification.
     *
     * @var string
     */
    public $id;

    /**
     * The locale to be used when sending the notification.
     *
     * @var string|null
     */
    public $locale;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }

    /**
     * Set the locale to send this notification in.
     *
     * @param  \UnitEnum|string  $locale
     * @return $this
     */
    public function locale($locale)
    {
        $this->locale = enum_value($locale);

        return $this;
    }
}
