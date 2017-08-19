<?php

namespace Illuminate\Mail\Events;

class MessageSent
{
    /**
     * The Swift message instance.
     *
     * @var \Swift_Message
     */
    public $message;
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  \Swift_Message  $message
     * @return void
     */
    public function __construct($message, $data = [])
    {
        $this->message = $message;
        $this->data = $data;
    }
}
