<?php

namespace Illuminate\Tests\Mail\Fixtures;

use Illuminate\Mail\Mailable;

class GreetingMailable extends Mailable
{
    public function build()
    {
        return $this->view('greeting');
    }
}
