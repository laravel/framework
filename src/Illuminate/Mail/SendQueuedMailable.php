<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailer;
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
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        $mailer->send($this->mailable);
    }
}
