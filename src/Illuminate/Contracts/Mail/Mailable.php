<?php

namespace Illuminate\Contracts\Mail;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory as Queue;

interface Mailable
{
    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @param  \Illuminate\Contracts\Events\Dispatcher|null $dispatcher
     * @return void
     */
    public function send(Mailer $mailer, Dispatcher $dispatcher = null);

    /**
     * Queue the given message.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @param  \Illuminate\Contracts\Events\Dispatcher|null $dispatcher
     * @return mixed
     */
    public function queue(Queue $queue, Dispatcher $dispatcher = null);

    /**
     * Deliver the queued message after the given delay.
     *
     * @param  \DateTime|int  $delay
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return mixed
     */
    public function later($delay, Queue $queue);
}
