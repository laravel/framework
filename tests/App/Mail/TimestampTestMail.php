<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class TimestampTestMail extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('timestamp');
    }
}
