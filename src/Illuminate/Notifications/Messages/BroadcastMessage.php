<?php

namespace Illuminate\Notifications\Messages;

class BroadcastMessage
{
    /**
     * The data for the notification.
     *
     * @var array
     */
    public $data;

    /**
     * Determine if the message should be broadcasted immediately.
     *
     * @var array
     */
    public $broadcastNow = false;

    /**
     * Create a new message instance.
     *
     * @param  string  $content
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the message data.
     *
     * @param  array  $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Broadcast this message immediately.
     *
     * @return $this
     */
    public function now()
    {
        $this->broadcastNow = true;

        return $this;
    }
}
