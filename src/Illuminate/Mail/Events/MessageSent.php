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

    /**
     * The Swift transport
     *
     * @var string
     */
    public $transport;

    /**
     * The Swift send result - format depends on the transport.
     *
     * @var array
     */
    public $result;

    /**
     * Create a new event instance.
     *
     * @param  \Swift_Message  $message
     * @param  array  $result
     * @param  string  $transport
     * @return void
     */
    public function __construct($message, $result, $transport)
    {
        $this->message = $message;
        $this->transport = $transport;
        $this->result = $result;
    }
}
