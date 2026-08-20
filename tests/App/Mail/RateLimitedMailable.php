<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\Middleware\RateLimited;

class RateLimitedMailable extends Mailable
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

    public function middleware()
    {
        return [new RateLimited('limiter')];
    }
}
