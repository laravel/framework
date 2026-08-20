<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class MailableQueueableStubWithDeduplication extends Mailable implements ShouldQueue
{
    use Queueable;

    public function build(): self
    {
        $this
            ->subject('lorem ipsum')
            ->html('foo bar baz')
            ->to('foo@example.tld');

        return $this;
    }

    public function deduplicationId($payload, $queue)
    {
        return hash('sha256', $payload);
    }
}
