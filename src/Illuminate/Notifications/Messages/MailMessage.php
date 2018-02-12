<?php

namespace Illuminate\Notifications\Messages;

class MailMessage extends SimpleMessage
{
    /**
     * The view to be rendered.
     *
     * @var array|string
     */
    public $view;

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The Markdown template to render (if applicable).
     *
     * @var string|null
     */
    public $markdown = 'notifications::email';

    /**
     * The "from" information for the message.
     *
     * @var array
     */
    public $from = [];

    /**
     * The "reply to" information for the message.
     *
     * @var array
     */
    public $replyTo = [];

    /**
     * The "cc" information for the message.
     *
     * @var array
     */
    public $cc = [];

    /**
     * The "bcc" information for the message.
     *
     * @var array
     */
    public $bcc = [];

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
     * Priority level of the message.
     *
     * @var int
     */
    public $priority;

    /**
     * Set the view for the mail message.
     *
     * @param  array|string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, array $data = [])
    {
        $this->view = $view;
        $this->viewData = $data;

        $this->markdown = null;

        return $this;
    }

    /**
     * Set the Markdown template for the notification.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function markdown($view, array $data = [])
    {
        $this->markdown = $view;
        $this->viewData = $data;

        $this->view = null;

        return $this;
    }

    /**
     * Set the default markdown template.
     *
     * @param  string  $template
     * @return $this
     */
    public function template($template)
    {
        $this->markdown = $template;

        return $this;
    }

    /**
     * Set the from address for the mail message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->from = [$address, $name];

        return $this;
    }

    /**
     * Set the "reply to" address of the message.
     *
     * @param  array|string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        $this->replyTo = [$address, $name];

        return $this;
    }

    /**
     * Set the cc address for the mail message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        $this->cc = [$address, $name];

        return $this;
    }

    /**
     * Set the bcc address for the mail message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        $this->bcc = [$address, $name];

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
     * Set the priority of this message.
     *
     * The value is an integer where 1 is the highest priority and 5 is the lowest.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->priority = $level;

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
