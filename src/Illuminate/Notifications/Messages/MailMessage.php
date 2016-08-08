<?php

namespace Illuminate\Notifications\Messages;

class MailMessage extends SimpleMessage
{
    /**
     * The view for the message.
     *
     * @var string
     */
    public $view = 'notifications::email';

    /**
     * Set the view for the mail message.
     *
     * @param  string  $view
     * @return $this
     */
    public function view($view)
    {
        $this->view = $view;

        return $this;
    }
}
