<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BasicMailableWithTheme extends Mailable
{
    public $theme = 'taylor';

    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'basic',
        );
    }
}
