<?php

namespace Illuminate\Mail\Events;

class MessageSending
{
    /**
     * The message data.
     *
     * @var array
     */
    public $data;

    /**
     * The Swift message instance.
     *
     * @var \Swift_Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @param  \Swift_Message $message
     * @param  array $data
     * @return void
     */
    public function __construct($message, $data = [])
    {
        $this->message = $message;
        $this->data = $data;
    }
}
