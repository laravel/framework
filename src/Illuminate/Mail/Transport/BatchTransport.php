<?php

namespace Illuminate\Mail\Transport;

use Symfony\Component\Mime\Email;

/**
 * A transport that is able to send several messages in a single request.
 *
 * Batch requests are made by calling "sendBatch" directly rather than the transport's
 * "send" method, so Symfony's MessageEvent listeners and "setMaxPerSecond" throttle do
 * not apply to them. Batches are paced using the batch's own interval instead.
 */
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
     * Failures of individual messages are returned, keyed like the given messages. Any
     * message that is not returned as a failure is considered sent. An exception should
     * only be thrown when the request as a whole failed and no message has been sent.
     *
     * @param  array<int, \Symfony\Component\Mailer\SentMessage>  $messages
     * @return array<int, \Throwable>
     *
     * @throws \Throwable
     */
    public function sendBatch(array $messages): array;
}
