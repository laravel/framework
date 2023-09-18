<?php

namespace Illuminate\Notifications\Exceptions;

use Illuminate\Notifications\Notification;

class DatabaseTypeNotificationMissingException extends \RuntimeException
{
    /**
     * The name of the affected Notification.
     */
    public Notification $notification;

    /**
     * Create a new exception instance.
     */
    public function __construct(Notification $notification)
    {
        $class = get_class($notification);

        parent::__construct("No database type defined for notification [{$class}].");

        $this->notification = $notification;
    }
}
