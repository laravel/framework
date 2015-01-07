<?php

namespace Illuminate\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getMessageBag();
}
