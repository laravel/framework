<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableWithQueue extends Mailable
{
    public $queue = 'mail-queue';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('view');
    }
}
