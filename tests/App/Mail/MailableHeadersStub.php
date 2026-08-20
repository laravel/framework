<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;

class MailableHeadersStub extends Mailable
{
    public function headers()
    {
        return new Headers('custom-message-id@example.com', [
            'previous-message@example.com',
        ], [
            'X-Custom-Header' => 'Custom Value',
        ]);
    }
}
