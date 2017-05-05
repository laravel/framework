<?php

namespace Illuminate\Auth\Access;

class Response
{
    /**
     * The response message.
     *
     * @var string
     */
    protected $message = '';

    /**
     * Create a new response.
     *
     * @param  string  $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Get the response message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }

    /**
     * Get the string representation of the message.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->message();
    }
}
