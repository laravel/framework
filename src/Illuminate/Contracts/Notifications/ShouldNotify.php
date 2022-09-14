<?php

namespace Illuminate\Contracts\Notifications;

interface ShouldNotify
{
    /**
     * Get the notifiables the event should notify.
     *
     * @return object|object[]
     */
    public function notifyTo();
}
