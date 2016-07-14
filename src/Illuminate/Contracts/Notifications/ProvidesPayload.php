<?php

namespace Illuminate\Contracts\Notifications;

interface ProvidesPayload
{
    /**
     * Get notification payload.
     *
     * @return  array
     */
    public function getPayload();
}
