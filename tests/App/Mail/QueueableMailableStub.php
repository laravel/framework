<?php

namespace Illuminate\Tests\App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class QueueableMailableStub extends Mailable implements ShouldQueue
{
    use Queueable;

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
