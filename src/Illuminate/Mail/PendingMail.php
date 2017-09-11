<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;

class PendingMail
{
    /**
     * The mailer instance.
     *
     * @var \Illuminate\Mail\Mailer
     */
    protected $mailer;

    /**
     * The "to" recipients of the message.
     *
     * @var array
     */
    protected $to = [];

    /**
     * The "cc" recipients of the message.
     *
     * @var array
     */
    protected $cc = [];

    /**
     * The "bcc" recipients of the message.
     *
     * @var array
     */
    protected $bcc = [];

    /**
     * Create a new mailable mailer instance.
     *
     * @param  \Illuminate\Mail\Mailer  $mailer
     * @return void
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Set the recipients of the message.
     *
     * @param  mixed  $users
     * @return $this
     */
    public function to($users)
    {
        $this->to = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param  mixed  $users
     * @return $this
     */
    public function cc($users)
    {
        $this->cc = $users;

        return $this;
    }

    /**
     * Set the recipients of the message.
     *
     * @param  mixed  $users
     * @return $this
     */
    public function bcc($users)
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * Send a new mailable message instance.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        if ($mailable instanceof ShouldQueue) {
            return $this->queue($mailable);
        }

        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Send a mailable message immediately.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @return mixed
     */
    public function sendNow(Mailable $mailable)
    {
        return $this->mailer->send($this->fill($mailable));
    }

    /**
     * Push the given mailable onto the queue.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        $mailable = $this->fill($mailable);

        if (isset($mailable->delay)) {
            return $this->mailer->later($mailable->delay, $mailable);
        }

        return $this->mailer->queue($mailable);
    }

    /**
     * Deliver the queued message after the given delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @return mixed
     */
    public function later($delay, Mailable $mailable)
    {
        return $this->mailer->later($delay, $this->fill($mailable));
    }

    /**
     * Populate the mailable with the addresses.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @return \Illuminate\Mail\Mailable
     */
    protected function fill(Mailable $mailable)
    {
        return $mailable->to($this->to)
                        ->cc($this->cc)
                        ->bcc($this->bcc);
    }
}
