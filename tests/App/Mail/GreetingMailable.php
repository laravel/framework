<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class GreetingMailable extends Mailable
{
    public function build()
    {
        return $this->view('greeting');
    }
}
