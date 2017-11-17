<?php

namespace Illuminate\Auth\Access;

class Response
{
    /**
     * The response message.
     *
     * @var string|null
     */
    protected $message;

    /**
     * Create a new response.
     *
     * @param  string|null  $message
     * @return void
     */
    public function __construct($message = null)
    {
        $this->message = $message;
    }

    /**
     * Get the response message.
     *
     * @return string|null
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
