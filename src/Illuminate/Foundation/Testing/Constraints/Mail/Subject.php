<?php

namespace Illuminate\Foundation\Testing\Constraints\Mail;

class Subject
{
    protected $subject;

    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param \Swift_Message $message
     */
    public function matches(\Swift_Message $message)
    {
        return $this->subject === $message->getSubject();
    }

    public function __toString()
    {
        return "Subject: {$this->subject}";
    }
}
