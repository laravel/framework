<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableWithExplicitFrom extends Mailable
{
    public function build()
    {
        return $this->view('view')
            ->from('hello@laravel.com');
    }
}
