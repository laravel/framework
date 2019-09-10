<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\Mailer as MailerContract;

class SendQueuedMailable
{
    /**
     * The mailable message instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailable
     */
    public $mailable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Contracts\Mail\Mailable  $mailable
     * @return void
     */
    public function __construct(MailableContract $mailable)
    {
        $this->mailable = $mailable;
        $this->tries = property_exists($mailable, 'tries') ? $mailable->tries : null;
        $this->timeout = property_exists($mailable, 'timeout') ? $mailable->timeout : null;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        $this->mailable->send($mailer);
    }

    /**
     * Get the display name for the queued job.
     *
     * @return string
     */
    public function displayName()
    {
        return get_class($this->mailable);
    }

    /**
     * Call the failed method on the mailable instance.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function failed($e)
    {
        if (method_exists($this->mailable, 'failed')) {
            $this->mailable->failed($e);
        }
    }

    /**
     * Get the retry delay for the mailable object.
     *
     * @return mixed
     */
    public function retryAfter()
    {
        if (! method_exists($this->mailable, 'retryAfter') && ! isset($this->mailable->retryAfter)) {
            return;
        }

        return $this->mailable->retryAfter ?? $this->mailable->retryAfter();
    }

    /**
     * Prepare the instance for cloning.
     *
     * @return void
     */
    public function __clone()
    {
        $this->mailable = clone $this->mailable;
    }
}
