<?php

namespace Illuminate\Mail\Events;

class MailableQueued
{
    /**
     * The Mailable instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailable
     */
    public $mailable;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable $mailable
     * @return void
     */
    public function __construct($mailable)
    {
        $this->mailable = $mailable;
    }
}
