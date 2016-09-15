<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\MailableMailer;

class MailableFake extends MailableMailer
{
    /**
     * The mailable instance.
     *
     * @var mixed
     */
    public $mailable;

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Send a new mailable message instance.
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        return $this->sendNow($mailable);
    }

    /**
     * Send a mailable message immediately.
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function sendNow(Mailable $mailable)
    {
        $this->mailable = $mailable;
    }

    /**
     * Push the given mailable onto the queue.
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        return $this->sendNow($mailable);
    }

    /**
     * Get the recipient information for the mailable.
     *
     * @return array
     */
    public function getRecipients()
    {
        return ['to' => $this->to, 'cc' => $this->cc, 'bcc' => $this->bcc];
    }
}
