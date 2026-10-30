<?php

namespace Illuminate\Broadcasting;

use Illuminate\Contracts\Broadcasting\HasBroadcastChannel;

class EncryptedPrivateChannel extends Channel
{
    /**
     * Create a new channel instance.
     *
     * @param  \Illuminate\Contracts\Broadcasting\HasBroadcastChannel|string  $name
     */
    public function __construct($name)
    {
        $name = $name instanceof HasBroadcastChannel ? $name->broadcastChannel() : $name;

        parent::__construct('private-encrypted-'.$name);
    }
}
