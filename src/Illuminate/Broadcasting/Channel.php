<?php

namespace Illuminate\Broadcasting;

class Channel
{
    /**
     * The channel's name.
     *
     * @var string
     */
    public $name;

    /**
     * Create a new channel instance.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Convert the channel instance to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
