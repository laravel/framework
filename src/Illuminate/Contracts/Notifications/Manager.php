<?php

namespace Illuminate\Contracts\Notifications;

interface Manager extends Factory, Dispatcher
{
    /**
     * Set the locale of notifications.
     *
     * @param  string  $locale
     * @return $this
     */
    public function locale($locale);

    /**
     * Set the default channel driver name.
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel);

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function deliversVia();

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function getDefaultDriver();
}
