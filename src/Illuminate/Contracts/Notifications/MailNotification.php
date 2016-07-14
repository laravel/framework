<?php

namespace Illuminate\Contracts\Notifications;

interface MailNotification
{
    /**
     * Build mail message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return void
     */
    public function buildMessage($message);
}
