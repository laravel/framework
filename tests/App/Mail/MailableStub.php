<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;

class MailableStub extends Mailable
{
    public $framework = 'Laravel';

    protected $version = '6.0';

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->with('first_name', 'Taylor')
            ->withLastName('Otwell');
    }
}
