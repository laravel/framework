<?php

namespace Illuminate\Contracts\Notifications;

interface Factory extends Dispatcher
{
    /**
     * Get a channel instance by name.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null);
}
