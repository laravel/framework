<?php

namespace Illuminate\Contracts\Mail;

interface MailQueue
{
    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  array   $data
     * @param  \Closure|string  $callback
     * @param  string  $queue
     * @return mixed
     */
    public function queue($view, array $data, $callback, $queue = null);

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $view, array $data, $callback, $queue = null);
}
