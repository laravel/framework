<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmbedImageMailable extends Mailable
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
            markdown: 'embed-image',
            with: [
                'image' => __DIR__.'/../../Integration/Mail/Fixtures/empty_image.jpg',
            ]
        );
    }
}
