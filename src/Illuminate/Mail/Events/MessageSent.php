<?php

namespace Illuminate\Mail\Events;

use Swift_Message;

class MessageSent
{
    /**
     * The Swift message instance.
     *
     * @var \Swift_Message
     */
    public $message;

    /**
     * The Swift send result - format depends on the transport.
     *
     * @var array
     */
    public $result;

    /**
     * The Swift transport.
     *
     * @var string
     */
    public $transport;

    /**
     * Create a new event instance.
     *
     * @param  \Swift_Message  $message
     * @param  array  $result
     * @param  string  $transport
     * @return void
     */
    public function __construct(Swift_Message $message, $result, $transport)
    {
        $this->message = $message;
        $this->transport = $transport;
        $this->result = $result;
    }
}
