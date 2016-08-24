<?php

namespace Illuminate\Mail\Events;

use Swift_Message;

class MessageNotSent
{
    /**
     * The Swift message instance.
     *
     * @var \Swift_Message
     */
    public $message;

    /**
     * The exception thrown.
     *
     * @var \Throwable
     */
    public $exception;

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
     * @param  \Throwable  $exception
     * @param  string  $transport
     * @return void
     */
    public function __construct(Swift_Message $message, $exception, $transport)
    {
        $this->message = $message;
        $this->transport = $transport;
        $this->exception = $exception;
    }
}
