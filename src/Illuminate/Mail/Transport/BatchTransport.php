<?php

namespace Illuminate\Mail\Transport;

use Symfony\Component\Mime\Email;

interface BatchTransport
{
    /**
     * Get the maximum number of messages that may be sent in a single request.
     *
     * @return int
     */
    public function maxBatchSize(): int;

    /**
     * Determine if the given message may be sent as part of a batch request.
     *
     * @param  \Symfony\Component\Mime\Email  $email
     * @return bool
     */
    public function canBatch(Email $email): bool;

    /**
     * Send the given messages in a single request.
     *
     * @param  array<int, \Symfony\Component\Mailer\SentMessage>  $messages
     * @return array<int, \Throwable>
     *
     * @throws \Throwable
     */
    public function sendBatch(array $messages): array;
}
