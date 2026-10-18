<?php

namespace Illuminate\Mail;

use RuntimeException;

class BatchSendException extends RuntimeException
{
    /**
     * Create a new batch send exception instance.
     *
     * @param  \Illuminate\Mail\BatchResult  $result
     */
    public function __construct(public BatchResult $result)
    {
        $failures = $result->failures();

        parent::__construct(
            sprintf('%d of %d messages in the batch failed to send.', count($failures), count($result)),
            0,
            reset($failures) ?: null
        );
    }
}
