<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableWithCallbackBuild extends Mailable
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build($builder)
    {
        $builder($this);
    }
}
