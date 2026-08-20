<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MessageAsWithNamedParameterMailable extends Mailable
{
    public function envelope()
    {
        return new Envelope(
            subject: 'My basic title',
        );
    }

    public function content()
    {
        return new Content(
            markdown: 'message',
            with: [
                'message' => 'My message',
            ]
        );
    }
}
