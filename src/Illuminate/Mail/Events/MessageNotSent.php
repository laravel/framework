<?php

namespace Illuminate\Mail\Events;

class MessageNotSent
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
     * The throwed Exception
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \Swift_Message  $message
     * @param  \Exception  $exception
     * @param  string  $transport
     * @return void
     */
    public function __construct($message, $exception, $transport)
    {
        $this->message = $message;
        $this->transport = $transport;
        $this->exception = $exception;
    }
}
