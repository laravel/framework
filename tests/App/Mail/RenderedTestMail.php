<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class RenderedTestMail extends Mailable
{
    public function build()
    {
        return $this->view('view');
    }
}
