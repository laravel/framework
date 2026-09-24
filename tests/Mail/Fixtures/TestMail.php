<?php

namespace Illuminate\Tests\Mail\Fixtures;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
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
