<?php

namespace Illuminate\Mail;

class MailableMailer
{
    /**
     * The mailer instance.
     *
     * @var array
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
     * @param  Mailer  $mailer
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
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function send(Mailable $mailable)
    {
        $mailable = $mailable->to($this->to)
                 ->cc($this->cc)
                 ->bcc($this->bcc);

        return $this->mailer->send($mailable);
    }

    /**
     * Queue a mailable message for sending.
     *
     * @param  Mailable  $mailable
     * @return mixed
     */
    public function queue(Mailable $mailable)
    {
        $mailable = $mailable->to($this->to)
                 ->cc($this->cc)
                 ->bcc($this->bcc);

        return $this->mailer->queue($mailable);
    }
}
