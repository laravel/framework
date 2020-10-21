<?php

namespace Illuminate\Contracts\Events;

interface DeferrableSubscriber
{
    /**
     * Get the events which are listened by the subscriber.
     *
     * @return string[]
     */
    public function listensTo();
}
