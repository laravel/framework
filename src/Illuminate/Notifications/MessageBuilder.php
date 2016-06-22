<?php

namespace Illuminate\Notifications;

class MessageBuilder
{
    /**
     * All of the message elements.
     *
     * @var array
     */
    public $elements = [];

    /**
     * Create a new message builder instance.
     *
     * @param  string  $line
     * @return void
     */
    public function __construct($line)
    {
        $this->elements[] = $line;
    }

    /**
     * Add a line to the message.
     *
     * @param  string  $line
     * @return $this
     */
    public function line($line)
    {
        $this->elements[] = $line;

        return $this;
    }

    /**
     * Add an action to the message.
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->elements[] = new Action($text, $url);

        return $this;
    }
}
