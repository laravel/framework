<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BasicMailableWithTextView extends Mailable
{
    public $textView = 'text';

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
