<?php

namespace Illuminate\Mail\Events;

use Symfony\Component\Mime\Email;
use Throwable;

class MessageFailed
{
    /**
     * Create a new event instance.
     *
     * @param  \Symfony\Component\Mime\Email  $message  The Symfony Email instance.
     * @param  \Throwable  $exception  The exception that was thrown while sending the message.
     * @param  array  $data  The message data.
     */
    public function __construct(
        public Email $message,
        public Throwable $exception,
        public array $data = [],
    ) {
    }
}
