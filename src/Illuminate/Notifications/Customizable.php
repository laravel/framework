<?php

namespace Illuminate\Notifications;

trait Customizable
{
    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    protected static $toMailCallback;

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
