<?php

namespace Illuminate\Notifications\Messages;

class NexmoMessage
{
    /**
     * The message content.
     *
     * @var string
     */
    public $content;

    /**
     * The phone number the message should be sent from.
     *
     * @var string
     */
    public $from;

    /**
     * Create a new message instance.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($content = '')
    {
        $this->content = $content;
    }

    /**
     * Set the message content.
     *
     * @param  string  $content
     * @return $this
     */
    public function content($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the phone number the message should be sent from.
     *
     * @param  string  $number
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;

        return $this;
    }
}
