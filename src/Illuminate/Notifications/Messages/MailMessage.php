<?php

namespace Illuminate\Notifications\Messages;

class MailMessage extends SimpleMessage
{
    /**
     * The view for the message.
     *
     * @var string
     */
    public $view = [
        'notifications::email',
        'notifications::email-plain',
    ];

    /**
     * The view data for the message.
     *
     * @var array
     */
    public $viewData = [];

    /**
     * The "from" information for the message.
     *
     * @var array
     */
    public $from = [];

    /**
     * The recipient information for the message.
     *
     * @var array
     */
    public $to = [];

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
    public $priority = null;

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
     * Set the recipient address for the mail message.
     *
     * @param  string|array  $address
     * @return $this
     */
    public function to($address)
    {
        $this->to = $address;

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
