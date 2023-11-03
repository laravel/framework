<?php

namespace Illuminate\Queue;

use InvalidArgumentException;
use JsonException;
use Throwable;

class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * The value that failed to decode.
     *
     * @var mixed
     */
    public $value;

    /**
     * Create a new exception instance.
     *
     * @param  string|null  $message
     * @param  mixed  $value
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct($message = null, $value = null, ?Throwable $previous = null)
    {
        parent::__construct($message ?: json_last_error(), 0, $previous);

        $this->value = $value;
    }

    /**
     * Get the exception context.
     *
     * @return array
     */
    public function context()
    {
        $context = [];

        $previous = $this->getPrevious();

        if ($previous instanceof JsonException) {
            $context['json-exception'] = [
                'code' => $previous->getCode(),
                'message' => $previous->getMessage(),
            ];
        }

        return $context;
    }
}
