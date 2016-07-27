<?php

namespace Illuminate\Notifications;

use Illuminate\Support\Traits\Macroable;

class MessageBuilder
{
    use Macroable;

    /**
     * The underlying notfication instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * All of the message elements.
     *
     * @var array
     */
    public $elements = [];

    /**
     * Create a new message builder instance.
     *
     * @param  mixed  $instance
     * @param  string  $line
     * @return void
     */
    public function __construct($instance, $line)
    {
        $this->elements[] = $line;
    }

    /**
     * Add a line to the message.
     *
     * @param  string  $line
     * @return $this
     */
    public function line($line)
    {
        $this->elements[] = $line;

        return $this;
    }

    /**
     * Add an action to the message.
     *
     * @param  string  $text
     * @param  string  $url
     * @return $this
     */
    public function action($text, $url)
    {
        $this->elements[] = new Action($text, $url);

        return $this;
    }

    /**
     * Set the "level" of the notification (success, error, etc.).
     *
     * @param  string  $level
     * @return $this
     */
    public function level($level)
    {
        $this->instance->level($level);

        return $this;
    }

    /**
     * Indicate that the notification gives information about a successful operation.
     *
     * @return $this
     */
    public function success()
    {
        $this->instance->level('success');

        return $this;
    }

    /**
     * Indicate that the notification gives information about an error.
     *
     * @return $this
     */
    public function error()
    {
        $this->instance->level('error');

        return $this;
    }

    /**
     * Set the subject of the notification.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->instance->subject($subject);

        return $this;
    }
}
