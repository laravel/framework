<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\Mailable as MailableContract;

class SendQueuedMailable
{
    /**
     * The mailable message instance.
     *
     * @var Mailable
     */
    protected $mailable;

    /**
     * Create a new job instance.
     *
     * @param  MailableContract  $mailable
     * @return void
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;
    }

    /**
     * Handle the queued job.
     *
     * @param  MailerContract  $mailer
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        $mailer->send($this->mailable);
    }
}
