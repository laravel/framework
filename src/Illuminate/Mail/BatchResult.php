<?php

namespace Illuminate\Mail;

use Countable;

class BatchResult implements Countable
{
    /**
     * Create a new batch result instance.
     *
     * @param  array<int, \Illuminate\Mail\SentMessage>  $sent
     * @param  array<int, \Throwable>  $failures
     * @param  int  $requests
     */
    public function __construct(
        protected array $sent = [],
        protected array $failures = [],
        protected int $requests = 0,
    ) {
    }

    /**
     * Get the messages that were sent, keyed by the order they were captured in.
     *
     * @return array<int, \Illuminate\Mail\SentMessage>
     */
    public function sent()
    {
        return $this->sent;
    }

    /**
     * Get the failures, keyed by the order the messages were captured in.
     *
     * @return array<int, \Throwable>
     */
    public function failures()
    {
        return $this->failures;
    }

    /**
     * Determine if every message in the batch was sent.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->failures === [];
    }

    /**
     * Determine if any message in the batch failed to send.
     *
     * @return bool
     */
    public function failed()
    {
        return ! $this->successful();
    }

    /**
     * Get the number of requests made to the transport.
     *
     * @return int
     */
    public function requests()
    {
        return $this->requests;
    }

    /**
     * Throw an exception if any message in the batch failed to send.
     *
     * @return $this
     *
     * @throws \Illuminate\Mail\BatchSendException
     */
    public function throw()
    {
        if ($this->failed()) {
            throw new BatchSendException($this);
        }

        return $this;
    }

    /**
     * Get the number of messages in the batch.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->sent) + count($this->failures);
    }
}
