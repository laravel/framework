<?php

namespace Illuminate\Mail\Transport;

use Symfony\Component\Mime\Message;
use Symfony\Component\Mailer\Transport\TransportInterface;

abstract class Transport implements TransportInterface
{
    /**
     * Get the number of recipients.
     *
     * @param  \Symfony\Component\Mime\Message  $message
     * @return int
     */
    protected function numberOfRecipients(Message $message)
    {
        return count(array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        ));
    }
}
