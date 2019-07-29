<?php

namespace Illuminate\Mail\Events;

class MessageSending
{
    /**
     * The Symfony Email instance.
     *
     * @var \Symfony\Component\Mime\Email
     */
    public $message;

    /**
     * The message data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  \Symfony\Component\Mime\Email $message
     * @param  array  $data
     * @return void
     */
    public function __construct($message, $data = [])
    {
        $this->data = $data;
        $this->message = $message;
    }
}
