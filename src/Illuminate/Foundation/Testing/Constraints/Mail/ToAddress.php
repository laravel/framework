<?php

namespace Illuminate\Foundation\Testing\Constraints\Mail;

class ToAddress
{
    protected $to;

    public function __construct($to)
    {
        $this->to = (array) $to;
    }

    public function matches(\Swift_Message $message)
    {
        return $this->to === array_keys($message->getTo());
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function __toString()
    {
        return 'To address(es): '.implode(', ', $this->to);
    }
}
