<?php

namespace Illuminate\Queue;

use InvalidArgumentException;

class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * The value that failed to decode.
     */
    public $value;

    /**
     * Create a new exception instance.
     *
     * @param  string|null  $message
     */
    public function __construct($message = null, $value = null)
    {
        parent::__construct($message ?: json_last_error());

        $this->value = $value;
    }
}
