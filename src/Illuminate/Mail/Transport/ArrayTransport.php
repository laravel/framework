<?php

namespace Illuminate\Mail\Transport;

use Illuminate\Support\Collection;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\SmtpEnvelope;

class ArrayTransport extends Transport
{
    /**
     * The collection of Swift Messages.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $messages;

    /**
     * Create a new array transport instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, SmtpEnvelope $envelope = null): ?SentMessage
    {
        $this->messages[] = $message;

        return null;
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Clear all of the messages from the local collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function flush()
    {
        return $this->messages = new Collection;
    }
}
