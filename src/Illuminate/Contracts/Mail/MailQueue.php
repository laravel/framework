<?php

namespace Illuminate\Contracts\Mail;

interface MailQueue
{
    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array|\Illuminate\Contracts\Mail\Mailable  $view
     * @param  string  $queue
     * @param  string|null $driver
     * @return mixed
     */
    public function queue($view, $queue = null, $driver = null);

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|array|\Illuminate\Contracts\Mail\Mailable  $view
     * @param  string  $queue
     * @param  string|null  $driver
     * @return mixed
     */
    public function later($delay, $view, $queue = null, $driver = null);
}
