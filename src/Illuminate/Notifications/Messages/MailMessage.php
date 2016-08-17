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
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The attachments for the message.
     *
     * @var array
     */
    public $attachments = [];

    /**
     * The raw attachments for the message.
     *
     * @var array
     */
    public $rawAttachments = [];

    /**
     * Set the view for the mail message.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $this->attachments[] = compact('file', 'options');

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->rawAttachments[] = compact('data', 'name', 'options');

        return $this;
    }

    /**
     * Get the data array for the mail message.
     *
     * @return array
     */
    public function data()
    {
        return array_merge($this->toArray(), $this->viewData);
    }
}
